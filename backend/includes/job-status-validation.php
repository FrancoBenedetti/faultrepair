<?php

/**
 * Job Status Transition Validation Engine
 * Validates state transitions according to business rules
 */

class JobStatusValidator {
    private $pdo;
    private $validTransitions = [
        // From -> To transitions with required conditions
        'Reported' => [
            'Quote Requested' => ['provider_selected' => true],
            'Assigned' => ['provider_selected' => true],
            'Rejected' => ['reason_required' => true]
        ],
        'Quote Requested' => [
            'Quote Provided' => ['provider_can_quote' => true],
            'Unable to quote' => ['reason_required' => true]
        ],
        'Unable to quote' => [
            'Reported' => ['reassignment' => true],
            'Rejected' => ['reason_required' => true]
        ],
        'Quote Rejected' => [
            'Quote Requested' => ['provider_selected' => true],
            'Reported' => ['reassignment' => true],
            'Rejected' => ['reason_required' => true]
        ],
        'Quote Expired' => [
            'Quote Requested' => ['provider_selected' => true],
            'Reported' => ['reassignment' => true],
            'Rejected' => ['reason_required' => true]
        ],
        'Quote Provided' => [
            'Assigned' => ['quote_accepted' => true],
            'Quote Requested' => ['revision_requested' => true],
            'Rejected' => ['reason_required' => true]
        ],
        'Assigned' => [
            'In Progress' => ['technician_assigned' => true],
            'Declined' => ['reason_required' => true]
        ],
        'In Progress' => [
            'Completed' => ['work_finished' => true],
            'Cannot repair' => ['reason_required' => true]
        ],
        'Completed' => [
            'Confirmed' => ['client_approval' => true],
            'Incomplete' => ['client_rejection' => true, 'reason_required' => true]
        ],
        'Cannot repair' => [
            'Confirmed' => ['client_approval' => true],
            'Incomplete' => ['provider_review' => true],
            'Assigned' => ['provider_selected' => true]
        ],
        'Incomplete' => [
            'In Progress' => ['technician_assigned' => true],
            'Completed' => ['rework_finished' => true, 'notes_required' => true]
        ],
        'Declined' => [
            'Rejected' => ['client_approval' => true],
            'Quote Requested' => ['provider_selected' => true],
            'Assigned' => ['provider_selected' => true]
        ]
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Validate if a status transition is allowed
     */
    public function validateTransition($jobId, $currentStatus, $newStatus, $userRole, $entityType, $additionalData = []) {
        // Check if transition exists in valid transitions
        if (!isset($this->validTransitions[$currentStatus][$newStatus])) {
            return [
                'valid' => false,
                'error' => "Transition from '$currentStatus' to '$newStatus' is not allowed"
            ];
        }

        $transitionRules = $this->validTransitions[$currentStatus][$newStatus];

        // Validate each rule for the transition
        foreach ($transitionRules as $rule => $required) {
            if (!$required) continue;

            $validationResult = $this->validateRule($jobId, $rule, $userRole, $entityType, $additionalData);
            if (!$validationResult['valid']) {
                return $validationResult;
            }
        }

        return ['valid' => true];
    }

    /**
     * Validate specific business rules
     */
    private function validateRule($jobId, $rule, $userRole, $entityType, $additionalData) {
        switch ($rule) {
            case 'provider_selected':
                return $this->validateProviderSelected($jobId);

            case 'reason_required':
                return $this->validateReasonRequired($additionalData);

            case 'technician_assigned':
                return $this->validateTechnicianAssigned($jobId);

            case 'provider_can_quote':
                return $this->validateProviderCanQuote($jobId);

            case 'quote_accepted':
                return $this->validateQuoteAccepted($jobId);

            case 'revision_requested':
                return $this->validateRevisionRequested($additionalData);

            case 'work_finished':
                return $this->validateWorkFinished($additionalData);

            case 'client_approval':
                return $this->validateClientApproval($jobId, $userRole, $entityType);

            case 'client_rejection':
                return $this->validateClientRejection($jobId, $userRole, $entityType);

            case 'provider_review':
                return $this->validateProviderReview($jobId, $userRole, $entityType);

            case 'rework_finished':
                return $this->validateReworkFinished($additionalData);

            case 'notes_required':
                return $this->validateNotesRequired($additionalData);

            default:
                return [
                    'valid' => false,
                    'error' => "Unknown validation rule: $rule"
                ];
        }
    }

    private function validateProviderSelected($jobId) {
        $stmt = $this->pdo->prepare("
            SELECT assigned_provider_participant_id
            FROM jobs
            WHERE id = ?
        ");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job || !$job['assigned_provider_participant_id']) {
            return [
                'valid' => false,
                'error' => 'Service provider must be selected for this transition'
            ];
        }

        return ['valid' => true];
    }

    private function validateReasonRequired($additionalData) {
        $notes = $additionalData['notes'] ?? '';
        if (empty(trim($notes))) {
            return [
                'valid' => false,
                'error' => 'A reason/note is required for this transition'
            ];
        }

        return ['valid' => true];
    }

    private function validateTechnicianAssigned($jobId) {
        $stmt = $this->pdo->prepare("
            SELECT j.assigned_technician_user_id, p.participantType
            FROM jobs j
            LEFT JOIN participants p ON j.assigned_provider_participant_id = p.participantId
            WHERE j.id = ?
        ");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        // Skip technician validation for XS providers - they manage technicians internally
        if ($job && strtoupper($job['participantType']) === 'XS') {
            return ['valid' => true];
        }

        if (!$job || !$job['assigned_technician_user_id']) {
            return [
                'valid' => false,
                'error' => 'Technician must be assigned for this transition'
            ];
        }

        return ['valid' => true];
    }

    private function validateProviderCanQuote($jobId) {
        // Check if provider is approved for this client
        $stmt = $this->pdo->prepare("
            SELECT j.assigned_provider_participant_id, l.participant_id as client_id
            FROM jobs j
            JOIN locations l ON j.client_location_id = l.id
            WHERE j.id = ?
        ");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job['assigned_provider_participant_id']) {
            return [
                'valid' => false,
                'error' => 'No service provider assigned'
            ];
        }

        // Check if provider is approved for this client
        $stmt = $this->pdo->prepare("
            SELECT id FROM participant_approvals
            WHERE client_participant_id = ? AND provider_participant_id = ?
        ");
        $stmt->execute([$job['client_id'], $job['assigned_provider_participant_id']]);

        if (!$stmt->fetch()) {
            return [
                'valid' => false,
                'error' => 'Service provider is not approved for this client'
            ];
        }

        return ['valid' => true];
    }

    private function validateQuoteAccepted($jobId) {
        // Check if there's an accepted quote for this job
        $stmt = $this->pdo->prepare("
            SELECT status FROM job_quotations
            WHERE job_id = ? AND status = 'accepted'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$jobId]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote) {
            return [
                'valid' => false,
                'error' => 'No accepted quote found for this job'
            ];
        }

        return ['valid' => true];
    }

    private function validateRevisionRequested($additionalData) {
        $notes = $additionalData['notes'] ?? '';
        if (empty(trim($notes))) {
            return [
                'valid' => false,
                'error' => 'Notes are required when requesting quote revision'
            ];
        }

        return ['valid' => true];
    }

    private function validateReassignment($jobId) {
        // Allow reassignment back to Reported state for provider who couldn't quote
        return ['valid' => true];
    }

    private function validateWorkFinished($additionalData) {
        // This is a subjective validation - in practice, this might involve
        // checking if certain tasks are completed or images are uploaded
        return ['valid' => true];
    }

    private function validateClientApproval($jobId, $userRole, $entityType) {
        // Only clients can provide approval
        if ($entityType !== 'client' || !in_array($userRole, [1, 2])) {
            return [
                'valid' => false,
                'error' => 'Only clients can approve jobs'
            ];
        }

        return ['valid' => true];
    }

    private function validateClientRejection($jobId, $userRole, $entityType) {
        // Only clients can reject completed work
        if ($entityType !== 'client' || !in_array($userRole, [1, 2])) {
            return [
                'valid' => false,
                'error' => 'Only clients can reject completed work'
            ];
        }

        return ['valid' => true];
    }

    private function validateProviderReview($jobId, $userRole, $entityType) {
        // Service provider admins can review cannot repair jobs
        if ($entityType !== 'service_provider' || $userRole !== 3) {
            return [
                'valid' => false,
                'error' => 'Only service provider admins can review cannot repair jobs'
            ];
        }

        return ['valid' => true];
    }

    private function validateReworkFinished($additionalData) {
        // Validate that rework is complete
        return ['valid' => true];
    }

    private function validateNotesRequired($additionalData) {
        $notes = $additionalData['notes'] ?? '';
        if (empty(trim($notes))) {
            return [
                'valid' => false,
                'error' => 'Notes are required for this transition'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get all valid next statuses for a given job and user
     */
    public function getValidNextStatuses($jobId, $currentStatus, $userRole, $entityType) {
        $validStatuses = [];

        if (isset($this->validTransitions[$currentStatus])) {
            foreach ($this->validTransitions[$currentStatus] as $nextStatus => $rules) {
                $validation = $this->validateTransition($jobId, $currentStatus, $nextStatus, $userRole, $entityType);
                if ($validation['valid']) {
                    $validStatuses[] = $nextStatus;
                }
            }
        }

        return $validStatuses;
    }
}
?>
