<?php

namespace App\Tasks;

use App\Traits\SecurityTrait;
use App\Utils\Logger;

class GrantAccessUserTask extends Task
{
    use SecurityTrait;

    public function getName(): string
    {
        return 'Grant access user';
    }

    public function execute(): void
    {
        $this->modx->lexicon->load('policy');
        $config = $this->getProperty('grant_access_user');
        if (empty($config)) {
            return;
        }

        foreach ($config as $key => $data) {
            Logger::info("Grant access for: {$key}");
            $users = $data['users'] ?? [];
            $groupName = $data['group_name'] ?? '';
            $permissions = $data['permissions'] ?? [];
            $contextKey = $data['context_key'] ?? null;
            $roleAuthority = $data['access_role_authority'] ?? 9;
            $roleName = empty($data['access_role_name']) ? $groupName : $data['access_role_name'];
            $policyName = empty($data['access_policy_name']) ? $groupName : $data['access_policy_name'];
            $templateName = empty($data['access_policy_template_name']) ? $groupName : $data['access_policy_template_name'];
            $mediaSourceOptions = $data['media_source'] ?? null;
            $sourceTemplate = $this->detectSourceAccessPolicyTemplate($data);

            if (!$contextKey || !$groupName || !$sourceTemplate) {
                continue;
            }


            $template = $this->getOrDuplicateAccessPolicyTemplate($templateName, $sourceTemplate->get('id'));
            if (!$template) {
                continue;
            }


            $permissions = $this->prepareTemplatePermissions($permissions, $sourceTemplate->get('id'), $data);
            $accessPolicy = $this->getOrCreateAccessPolicy($policyName, $template->get('id'), $permissions);
            if (!$accessPolicy) {
                continue;
            }

            $role = $this->getOrCreateUserGroupRole($roleName, $roleAuthority);
            if (!$role) {
                continue;
            }

            $userGroup = $this->getOrCreateUserGroup($groupName);
            if (!$userGroup) {
                continue;
            }

            $contextData = [
                'target' => $contextKey,
                'authority' => $roleAuthority,
                'policy' => $accessPolicy->get('id'),
                'principal' => $userGroup->get('id'),
            ];

            $context = $this->getOrCreateUserGroupAccessContext($contextData);
            if (!$context) {
                continue;
            }

            if ($contextKey === 'mgr') {
                $this->updateUserGroupAccessContext($userGroup->get('id'), 'web', [
                    'authority' => $roleAuthority,
                    'policy' => 2, // access policy - Administrator.
                ]);
            }

            $groupMember = array(
                'user_group' => $userGroup->get('id'),
                'role' => $role->get('id'),
            );

            foreach ($users as $user) {
                $this->getOrCreateUser($user, $groupMember);
            }

            if (!empty($mediaSourceOptions)) {
                if (empty($mediaSourceOptions['join'])) {
                    $this->createMediaSource($mediaSourceOptions, $userGroup->get('id'));
                } else {
                    $this->joinMediaSource($mediaSourceOptions['join'], $userGroup->get('id'), $mediaSourceOptions);
                }
            }
        }

    }

    protected function prepareTemplatePermissions(array $permissions, int $templateId = 0, array $options = []): ?array
    {
        if (!empty($options['access_policy_template_override'])) {
            $defaultPermissions = $this->getAccessPolicyTemplatePermissions($templateId, 0);
        } else {
            $defaultPermissions = $this->getAccessPolicyPermissions($templateId);
        }
        return array_merge($defaultPermissions, $permissions);
    }

    protected function detectSourceAccessPolicyTemplate(array $options = []): ?object
    {
        if (!empty($options['access_policy_template_override'])) {
            return $this->findAccessPolicyTemplate($options['access_policy_template_override']);

        } else if (!empty($options['access_policy_template_inherit'])) {
            return $this->findAccessPolicyTemplate($options['access_policy_template_inherit']);
        }
        return null;
    }

    protected function joinMediaSource(string $mediaSourceName, int $userGroupId, array $options = []): ?object
    {
        $mediaSource = $this->findMediaSource($mediaSourceName);
        if (!$mediaSource) {
            return null;
        }

        $access = $options['access'] ?? [];
        $access = array_merge([
            'target' => $mediaSource->get('id'),
            'authority' => 9,
            'policy' => 8,
            'principal_class' => 'modUserGroup',
            'principal' => $userGroupId,
        ], $access);

        if (!$this->getOrCreateAccessMediaSource($access)) {
            return null;
        }

        return $mediaSource;
    }

    protected function createMediaSource(array $options, int $userGroupId): void
    {

        $name = $options['name'] ?? '';
        $access = $options['access'] ?? [];
        $isBindTvs = $options['bind_tvs'] ?? false;
        $sourcePath = $options['source_path'] ?? '';

        if ($sourcePath) {
            $mediaSource = $this->getOrCreateFileMediaSource($name, $sourcePath);
            if (!$mediaSource) {
                return;
            }

            $access = array_merge([
                'target' => $mediaSource->get('id'),
                'principal_class' => 'modUserGroup',
                'principal' => $userGroupId,
            ], $access);

            $this->getOrCreateAccessMediaSource($access);

            $adminAccess = array(
                'target' => 1,
                'principal_class' => 'modUserGroup',
                'principal' => 1,
                'authority' => 0,
                'policy' => 8,
                'context_key' => '',
            );
            $this->getOrCreateAccessMediaSource($adminAccess);

            if ($isBindTvs) {
                $this->bindMediaSourceTvs($mediaSource->get('id'));
            }
        }
    }
}