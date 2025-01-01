<?php

namespace App\Traits;

use App\Utils\Logger;

trait SecurityTrait
{
    protected \modX $modx;

    protected function findAccessPolicyTemplate($template): ?object
    {
        if (is_numeric($template)) {
            return $this->modx->getObject('modAccessPolicyTemplate', $template);
        }
        return $this->modx->getObject('modAccessPolicyTemplate', ['name' => $template]);
    }

    protected function getOrDuplicateAccessPolicyTemplate(string $name, int $templateId = 1): ?object
    {
        if (!$policyTemplate = $this->findAccessPolicyTemplate($name)) {
            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/access/policy/template/duplicate', [
                'id' => $templateId,
            ]);

            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }

            $id = $response->getObject()['id'];
            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/access/policy/template/update', [
                'id' => $id,
                'name' => $name,
                'description' => '',
            ]);

            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }

            $policyTemplate = $this->modx->getObject('modAccessPolicyTemplate', $id);
        }

        return $policyTemplate;
    }

    protected function getOrCreateAccessPolicy(string $name, int $templatePolicyId, array $permissions): ?object
    {
        if (!$policy = $this->modx->getObject('modAccessPolicy', ['name' => $name])) {
            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/access/policy/create', [
                'name' => $name,
                'template' => $templatePolicyId,
            ]);

            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }

            $id = $response->getObject()['id'];
            if (!$policy = $this->modx->getObject('modAccessPolicy', $id)) {
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
        if (!$role = $this->modx->getObject('modUserGroupRole', ['name' => $name])) {
            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/role/create', [
                'name' => $name,
                'authority' => $authority,

            ]);
            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }
            $id = $response->getObject()['id'];
            $role = $this->modx->getObject('modUserGroupRole', $id);
        }
        return $role;
    }

    protected function getOrCreateUserGroup(string $name, array $params = []): ?object
    {
        if (!$group = $this->modx->getObject('modUserGroup', ['name' => $name])) {
            $params = array_merge([
                'name' => $name,
                'parent' => 1,
                'aw_contexts' => 'web',
            ], $params);

            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/group/create', $params);
            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }
            $id = $response->getObject()['id'];
            $group = $this->modx->getObject('modUserGroup', $id);
        }
        return $group;
    }

    protected function getOrCreateUserGroupAccessContext(array $contextData): ?object
    {
        if (!$context = $this->modx->getObject('modAccessContext', $contextData)) {
            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/access/usergroup/context/create', $contextData);

            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }
            $id = $response->getObject()['id'];
            $context = $this->modx->getObject('modAccessContext', $id);
        }
        return $context;
    }

    protected function updateUserGroupAccessContext(int $groupId, string $ctx, array $data): ?object
    {
        if ($context = $this->modx->getObject('modAccessContext', array('target' => $ctx, 'principal' => $groupId))) {
            $data = array_merge([
                'id' => $context->get('id'),
                'target' => $ctx,
                'principal' => $groupId,
            ], $data);

            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/access/usergroup/context/update', $data);
            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }
        }
        return $context;
    }

    protected function getOrCreateUserGroupMember(array $data): ?object
    {
        if (!$userGroupMember = $this->modx->getObject('modUserGroupMember', $data)) {
            $userGroupMember = $this->modx->newObject('modUserGroupMember');
            $userGroupMember->fromArray($data);
            if (!$userGroupMember->save()) {
                Logger::error("Error save user in group. Data: " . print_r($data, 1));
                return null;
            }
        }
        return $userGroupMember;
    }

    protected function getOrCreateUser(array $data, array $groupMember = []): ?object
    {
        if (!$user = $this->modx->getObject('modUser', ['username' => $data['username']])) {
            $password = empty($data['password']) ? $this->modx->user->generatePassword() : $data['password'];

            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/user/create', [
                'username' => $data['username'],
                'active' => 1,
                'class_key' => 'modUser',
                'newpassword' => false,
                'passwordnotifymethod' => 's',
                'passwordgenmethod' => 'spec',
                'specifiedpassword' => $password,
                'confirmpassword' => $password,
                'email' => empty($data['email']) ? mb_strtolower($data['username']) . '@' . MODX_HTTP_HOST : $data['email'],
            ]);

            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            } else {
                Logger::info($data['username'] . ' --> ' . $response->getMessage());
            }

            $userId = $response->getObject()['id'];
            $user = $this->modx->getObject('modUser', $userId);

            if ($groupMember) {
                $groupMember = array_merge(['member' => $userId], $groupMember);
                $this->getOrCreateUserGroupMember($groupMember);
            }
        }
        return $user;
    }

    protected function findMediaSource(string $name): ?object
    {
        return $this->modx->getObject('sources.modMediaSource', ['name' => $name]);
    }

    protected function getOrCreateFileMediaSource(string $name, string $path, array $accessMediaSource = []): ?object
    {
        if (!is_dir(MODX_BASE_PATH . $path)) {
            if (!$this->modx->cacheManager->writeTree(MODX_BASE_PATH . $path)) {
                Logger::error("Error create dir for media source {$name}");
                return null;
            }
        }

        $mediaSourceData = [
            'name' => $name,
            'class_key' => 'sources.modFileMediaSource',
        ];

        if (!$mediaSource = $this->modx->getObject('sources.modMediaSource', $mediaSourceData)) {
            $this->modx->error->reset();
            /** @var \modProcessorResponse $response */
            $response = $this->modx->runProcessor('source/create', $mediaSourceData);
            if ($response->isError()) {
                Logger::error($response->getResponse());
                return null;
            }
            $id = $response->getObject()['id'];
            $mediaSource = $this->modx->getObject('sources.modMediaSource', $id);
        }

        $mediaSourceProperties = $mediaSource->getProperties();
        $mediaSourceProperties['basePath']['value'] = '/' . $path;
        $mediaSourceProperties['baseUrl']['value'] = $path;
        $mediaSource->setProperties($mediaSourceProperties);

        if (!$mediaSource->save()) {
            Logger::error("Error save media source for {$name}");
            return null;
        }

        if ($accessMediaSource) {
            $accessMediaSource = array_merge(['target' => $mediaSource->id], $accessMediaSource);
            $acl = $this->getOrCreateAccessMediaSource($accessMediaSource);
            if (!$acl) {
                Logger::error("Error create file media source. Data: " . print_r($accessMediaSource, 1));
                return $mediaSource;
            }
        }

        return $mediaSource;
    }

    protected function getOrCreateAccessMediaSource(array $data = []): ?object
    {

        $data = array_merge([
            'target' => 1, // Filesystem ID
            'principal_class' => 'modUserGroup',
            'principal' => 1, // User Group - Administrator
            'authority' => 0, // min role
            'policy' => 8,    // policy - Media Source Admin
            'context_key' => '',
        ], $data);

        if (!$acl = $this->modx->getObject('sources.modAccessMediaSource', $data)) {
            $acl = $this->modx->newObject('sources.modAccessMediaSource');
            $acl->fromArray($data, '', true, true);
            if (!$acl->save()) {
                Logger::error("Error update access source data Filesystem. Data: " . print_r($data, 1));
                return null;
            }
        }
        return $acl;
    }

    protected function bindMediaSourceTvs(int $mediaSourceId, $contextKey = 'web', array $tvIds = [], bool $exclude = false): void
    {
        $classKey = 'modTemplateVar';
        $q = $this->modx->newQuery($classKey);

        if (!empty($tvIds)) {
            if ($exclude) {
                $q->where(["`{$classKey}`.`id`:NOT IN" => $tvIds]);
            } else {
                $q->where(["`{$classKey}`.`id`:IN" => $tvIds]);
            }

        }

        if ($tvs = $this->modx->getCollection($classKey, $q)) {
            foreach ($tvs as $tv) {
                $sourceElements = $this->modx->getCollection('sources.modMediaSourceElement', [
                    'object' => $tv->id,
                    'object_class' => 'modTemplateVar',
                    'context_key' => $contextKey,
                ]);
                foreach ($sourceElements as $sourceElement) {
                    $sourceElement->remove();
                }
                $sourceElement = $this->modx->newObject('sources.modMediaSourceElement');
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
        $classKey = 'modAccessPermission';
        $q = $this->modx->newQuery($classKey);
        $q->select($this->modx->getSelectColumns($classKey, $classKey, '', ['name', 'value', 'description']));
        $q->where([
            "`{$classKey}`.`template`" => $templateId
        ]);
        if ($q->prepare() && $q->stmt->execute()) {
            $this->modx->lexicon->load('permissions');
            while ($row = $q->stmt->fetch(\PDO::FETCH_ASSOC)) {
                $result[$row['name']] = $forceValue === null ? $row['value'] : $forceValue;
            }
        }

        return $result;
    }

    protected function getAccessPolicyPermissions(int $templateId): array
    {
        $data = [];
        $accessPolicy = $this->modx->getObject('modAccessPolicy', ['template' => $templateId]);
        if ($accessPolicy) {
            $data = $accessPolicy->get('data');
        }
        return $data;
    }

}