<?php

namespace App\Traits;

use App\Utils\Logger;
use modProcessorResponse;
use modX;
use MODX\Revolution\modAccessContext;
use MODX\Revolution\modAccessPermission;
use MODX\Revolution\modAccessPolicy;
use MODX\Revolution\modAccessPolicyTemplate;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\modUser;
use MODX\Revolution\modUserGroup;
use MODX\Revolution\modUserGroupMember;
use MODX\Revolution\modUserGroupRole;
use MODX\Revolution\Processors\Security\Access\Policy\Create as PolicyCreate;
use MODX\Revolution\Processors\Security\Access\Policy\Template\Duplicate;
use MODX\Revolution\Processors\Security\Access\Policy\Template\Update;
use MODX\Revolution\Processors\Security\Access\UserGroup\Context\Create as UserGroupContextCreate;
use MODX\Revolution\Processors\Security\Access\UserGroup\Context\Update as UserGroupContextUpdate;
use MODX\Revolution\Processors\Security\Group\Create as GroupCreate;
use MODX\Revolution\Processors\Security\Role\Create as RoleCreate;
use MODX\Revolution\Processors\Security\User\Create as UserCreate;
use MODX\Revolution\Processors\Source\Create as SourceCreate;
use MODX\Revolution\Sources\modMediaSource;
use MODX\Revolution\Sources\modMediaSourceElement;
use PDO;

trait SecurityTrait
{
    protected modX $modx;

    protected function findAccessPolicyTemplate($template): ?object
    {
        if (is_numeric($template)) {
            return $this->modx->getObject(modAccessPolicyTemplate::class, $template);
        }

        return $this->modx->getObject(modAccessPolicyTemplate::class, ['name' => $template]);
    }

    protected function getOrDuplicateAccessPolicyTemplate(string $name, int $templateId = 1): ?object
    {
        if (!$policyTemplate = $this->findAccessPolicyTemplate($name)) {
            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(Duplicate::class, [
                'id' => $templateId,
            ]);

            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }

            $id = $response->getObject()['id'];
            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(Update::class, [
                'id' => $id,
                'name' => $name,
                'description' => '',
            ]);

            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }

            $policyTemplate = $this->modx->getObject(modAccessPolicyTemplate::class, $id);
        }

        return $policyTemplate;
    }

    protected function getOrCreateAccessPolicy(string $name, int $templatePolicyId, array $permissions): ?object
    {
        if (!$policy = $this->modx->getObject(modAccessPolicy::class, ['name' => $name])) {
            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(PolicyCreate::class, [
                'name' => $name,
                'template' => $templatePolicyId,
            ]);

            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }

            $id = $response->getObject()['id'];
            if (!$policy = $this->modx->getObject(modAccessPolicy::class, $id)) {
                Logger::error($response->getResponse());

                return null;
            }

            // $permissions = array_merge($policy->get('data'), $permissions);
            $policy->set('data', $this->modx->toJSON($permissions));
            if (!$policy->save()) {
                Logger::error("Failed to save policies for {$name}. Permissions: " . print_r($permissions, 1));

                return null;
            }
        }

        return $policy;
    }

    protected function getOrCreateUserGroupRole(string $name, int $authority = 9): ?object
    {
        if (!$role = $this->modx->getObject(modUserGroupRole::class, ['name' => $name])) {
            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(RoleCreate::class, [
                'name' => $name,
                'authority' => $authority,

            ]);
            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }
            $id = $response->getObject()['id'];
            $role = $this->modx->getObject(modUserGroupRole::class, $id);
        }

        return $role;
    }

    protected function getOrCreateUserGroup(string $name, array $params = []): ?object
    {
        if (!$group = $this->modx->getObject(modUserGroup::class, ['name' => $name])) {
            $params = array_merge([
                'name' => $name,
                'parent' => 1,
                'aw_contexts' => 'web',
            ], $params);

            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(GroupCreate::class, $params);
            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }
            $id = $response->getObject()['id'];
            $group = $this->modx->getObject(modUserGroup::class, $id);
        }

        return $group;
    }

    protected function getOrCreateUserGroupAccessContext(array $contextData): ?object
    {
        if (!$context = $this->modx->getObject(modAccessContext::class, $contextData)) {
            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(UserGroupContextCreate::class, $contextData);

            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }
            $id = $response->getObject()['id'];
            $context = $this->modx->getObject(modAccessContext::class, $id);
        }

        return $context;
    }

    protected function updateUserGroupAccessContext(int $groupId, string $ctx, array $data): ?object
    {
        if ($context = $this->modx->getObject(modAccessContext::class, ['target' => $ctx, 'principal' => $groupId])) {
            $data = array_merge([
                'id' => $context->get('id'),
                'target' => $ctx,
                'principal' => $groupId,
            ], $data);

            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(UserGroupContextUpdate::class, $data);
            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }
        }

        return $context;
    }

    protected function getOrCreateUserGroupMember(array $data): ?object
    {
        if (!$userGroupMember = $this->modx->getObject(modUserGroupMember::class, $data)) {
            $userGroupMember = $this->modx->newObject(modUserGroupMember::class);
            $userGroupMember->fromArray($data);
            if (!$userGroupMember->save()) {
                Logger::error('Error save user in group. Data: ' . print_r($data, 1));

                return null;
            }
        }

        return $userGroupMember;
    }

    protected function getOrCreateUser(array $data, array $groupMember = []): ?object
    {
        if (!$user = $this->modx->getObject(modUser::class, ['username' => $data['username']])) {
            $password = empty($data['password']) ? $this->modx->user->generatePassword() : $data['password'];

            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(UserCreate::class, [
                'username' => $data['username'],
                'active' => 1,
                'class_key' => modUser::class,
                'newpassword' => false,
                'passwordnotifymethod' => 's',
                'passwordgenmethod' => 'spec',
                'specifiedpassword' => $password,
                'confirmpassword' => $password,
                'email' => empty($data['email']) ? mb_strtolower((string) $data['username']) . '@' . MODX_HTTP_HOST : $data['email'],
            ]);

            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }
            Logger::info($data['username'] . ' --> ' . $response->getMessage());

            $userId = $response->getObject()['id'];
            $user = $this->modx->getObject(modUser::class, $userId);

            if ($groupMember !== []) {
                $groupMember = array_merge(['member' => $userId], $groupMember);
                $this->getOrCreateUserGroupMember($groupMember);
            }
        }

        return $user;
    }

    protected function findMediaSource(string $name): ?object
    {
        return $this->modx->getObject(modMediaSource::class, ['name' => $name]);
    }

    protected function getOrCreateFileMediaSource(string $name, string $path, array $accessMediaSource = []): ?object
    {
        if (!is_dir(MODX_BASE_PATH . $path) && !$this->modx->cacheManager->writeTree(MODX_BASE_PATH . $path)) {
            Logger::error("Error create dir for media source {$name}");

            return null;
        }

        $mediaSourceData = [
            'name' => $name,
            'class_key' => modMediaSource::class,
        ];

        if (!$mediaSource = $this->modx->getObject(modMediaSource::class, $mediaSourceData)) {
            $this->modx->error->reset();
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(SourceCreate::class, $mediaSourceData);
            if ($response->isError()) {
                Logger::error($response->getResponse());

                return null;
            }
            $id = $response->getObject()['id'];
            $mediaSource = $this->modx->getObject(modMediaSource::class, $id);
        }

        $mediaSourceProperties = $mediaSource->getProperties();
        $mediaSourceProperties['basePath']['value'] = '/' . $path;
        $mediaSourceProperties['baseUrl']['value'] = $path;
        $mediaSource->setProperties($mediaSourceProperties);

        if (!$mediaSource->save()) {
            Logger::error("Error save media source for {$name}");

            return null;
        }

        if ($accessMediaSource !== []) {
            $accessMediaSource = array_merge(['target' => $mediaSource->id], $accessMediaSource);
            $acl = $this->getOrCreateAccessMediaSource($accessMediaSource);
            if (!$acl) {
                Logger::error('Error create file media source. Data: ' . print_r($accessMediaSource, 1));

                return $mediaSource;
            }
        }

        return $mediaSource;
    }

    protected function getOrCreateAccessMediaSource(array $data = []): ?object
    {

        $data = array_merge([
            'target' => 1, // Filesystem ID
            'principal_class' => modUserGroup::class,
            'principal' => 1, // User Group - Administrator
            'authority' => 0, // min role
            'policy' => 8,    // policy - Media Source Admin
            'context_key' => '',
        ], $data);

        if (!$acl = $this->modx->getObject('sources.modAccessMediaSource', $data)) {
            $acl = $this->modx->newObject('sources.modAccessMediaSource');
            $acl->fromArray($data, '', true, true);
            if (!$acl->save()) {
                Logger::error('Error update access source data Filesystem. Data: ' . print_r($data, 1));

                return null;
            }
        }

        return $acl;
    }

    protected function bindMediaSourceTvs(int $mediaSourceId, $contextKey = 'web', array $tvIds = [], bool $exclude = false): void
    {
        $classKey = modTemplateVar::class;
        $q = $this->modx->newQuery($classKey);

        if ($tvIds !== []) {
            if ($exclude) {
                $q->where(['`modTemplateVar`.`id`:NOT IN' => $tvIds]);
            } else {
                $q->where(['`modTemplateVar`.`id`:IN' => $tvIds]);
            }

        }

        if ($tvs = $this->modx->getCollection($classKey, $q)) {
            foreach ($tvs as $tv) {
                $sourceElements = $this->modx->getCollection(modMediaSourceElement::class, [
                    'object' => $tv->id,
                    'object_class' => modTemplateVar::class,
                    'context_key' => $contextKey,
                ]);
                foreach ($sourceElements as $sourceElement) {
                    $sourceElement->remove();
                }
                $sourceElement = $this->modx->newObject(modMediaSourceElement::class);
                $sourceElement->fromArray([
                    'object' => $tv->id,
                    'object_class' => $tv->_class,
                    'context_key' => $contextKey,
                ], '', true, true);
                $sourceElement->set('source', $mediaSourceId);
                if (!$sourceElement->save()) {
                    Logger::error("Error bind media source ID: {$mediaSourceId} for tv: {$tv->id}.");
                }
            }
        }
    }

    protected function getAccessPolicyTemplatePermissions(int $templateId, $forceValue = null): array
    {
        $result = [];
        $classKey = modAccessPermission::class;
        $q = $this->modx->newQuery($classKey);
        $q->select($this->modx->getSelectColumns($classKey, $classKey, '', ['name', 'value', 'description']));
        $q->where([
            '`modAccessPermission`.`template`' => $templateId,
        ]);
        if ($q->prepare() && $q->stmt->execute()) {
            $this->modx->lexicon->load('permissions');
            while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['name']] = $forceValue ?? $row['value'];
            }
        }

        return $result;
    }

    protected function getAccessPolicyPermissions(int $templateId): array
    {
        $data = [];
        $accessPolicy = $this->modx->getObject(modAccessPolicy::class, ['template' => $templateId]);
        if ($accessPolicy) {
            return $accessPolicy->get('data');
        }

        return $data;
    }
}
