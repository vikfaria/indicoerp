<?php

namespace App\Services;

use App\Models\PurchaseReturn;
use App\Models\SalesInvoiceReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Workdo\Account\Models\CreditNote;

class FiscalDocumentComplianceService
{
    /**
     * @var array<int, string>
     */
    private const SUBMISSION_STATUSES = ['pending', 'submitted', 'validated', 'rejected', 'not_required'];

    public function updateSubmissionStatus(
        Model $document,
        string $status,
        ?string $reference = null,
        ?string $message = null
    ): void {
        $status = strtolower(trim($status));

        if (!in_array($status, self::SUBMISSION_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => __('Invalid fiscal submission status.'),
            ]);
        }

        if ($this->isCancelled($document)) {
            throw ValidationException::withMessages([
                'status' => __('Cannot update fiscal status on cancelled documents.'),
            ]);
        }

        $currentStatus = strtolower((string) ($document->getAttribute('fiscal_submission_status') ?: 'pending'));
        $reference = $this->normaliseText($reference);
        $message = $this->normaliseText($message);

        $updates = [
            'fiscal_submission_status' => $status,
        ];

        if (in_array($status, ['submitted', 'validated'], true) && $reference === null) {
            throw ValidationException::withMessages([
                'reference' => __('A fiscal reference is required for submitted or validated status.'),
            ]);
        }

        if ($status === 'submitted') {
            if (!$this->canBeSubmitted($document)) {
                throw ValidationException::withMessages([
                    'status' => __('Only operationally confirmed documents can be submitted.'),
                ]);
            }

            $updates['fiscal_submission_reference'] = $reference;
            $updates['fiscal_submitted_at'] = now();
            $updates['fiscal_validation_message'] = null;
        }

        if ($status === 'validated') {
            if (!in_array($currentStatus, ['submitted', 'validated'], true)) {
                throw ValidationException::withMessages([
                    'status' => __('Document must be submitted before validation.'),
                ]);
            }

            $updates['fiscal_submission_reference'] = $reference;
            $updates['fiscal_validated_at'] = now();
            $updates['fiscal_validation_message'] = $message;
        }

        if ($status === 'rejected') {
            if (!in_array($currentStatus, ['submitted', 'validated', 'rejected'], true)) {
                throw ValidationException::withMessages([
                    'status' => __('Only submitted documents can be rejected.'),
                ]);
            }

            $updates['fiscal_validation_message'] = $message;
            $updates['fiscal_validated_at'] = null;
        }

        if (in_array($status, ['pending', 'not_required'], true)) {
            $updates['fiscal_submitted_at'] = $status === 'pending' ? $document->getAttribute('fiscal_submitted_at') : null;
            $updates['fiscal_validated_at'] = null;
            $updates['fiscal_validation_message'] = $message;
        }

        $document->forceFill($updates)->save();
    }

    public function cancelDocument(
        Model $document,
        string $reason,
        ?string $cancellationReference = null,
        ?string $rectificationReference = null,
        ?int $cancelledBy = null
    ): void {
        $reason = $this->normaliseText($reason);
        $cancellationReference = $this->normaliseText($cancellationReference);
        $rectificationReference = $this->normaliseText($rectificationReference);

        if ($reason === null || mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => __('Cancellation reason must contain at least 5 characters.'),
            ]);
        }

        if ($this->isCancelled($document)) {
            throw ValidationException::withMessages([
                'reason' => __('Document is already cancelled.'),
            ]);
        }

        if (!$this->canBeCancelled($document)) {
            throw ValidationException::withMessages([
                'reason' => __('Draft documents cannot be cancelled in fiscal mode.'),
            ]);
        }

        $submissionStatus = strtolower((string) ($document->getAttribute('fiscal_submission_status') ?: 'pending'));
        if ($submissionStatus === 'validated' && $rectificationReference === null) {
            throw ValidationException::withMessages([
                'rectification_reference' => __('A rectification reference is required to cancel a validated document.'),
            ]);
        }

        $updates = [
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
            'cancellation_reference' => $cancellationReference,
            'rectification_reference' => $rectificationReference,
        ];

        if ($submissionStatus !== 'not_required') {
            $updates['fiscal_submission_status'] = 'rejected';
            $updates['fiscal_validation_message'] = __('Document cancelled');
            $updates['fiscal_validated_at'] = null;
        }

        if ($this->supportsCancelledStatus($document)) {
            $updates['status'] = 'cancelled';
        }

        $document->forceFill($updates)->save();
    }

    private function canBeSubmitted(Model $document): bool
    {
        if (!$document->offsetExists('status')) {
            return true;
        }

        $status = strtolower((string) $document->getAttribute('status'));

        if ($status === '') {
            return true;
        }

        return in_array($status, ['posted', 'partial', 'paid', 'approved', 'completed', 'sent', 'accepted'], true);
    }

    private function canBeCancelled(Model $document): bool
    {
        if (!$document->offsetExists('status')) {
            return true;
        }

        $status = strtolower((string) $document->getAttribute('status'));

        return !in_array($status, ['', 'draft', 'cancelled'], true);
    }

    private function supportsCancelledStatus(Model $document): bool
    {
        return $document instanceof SalesInvoiceReturn
            || $document instanceof PurchaseReturn
            || $document instanceof CreditNote;
    }

    private function isCancelled(Model $document): bool
    {
        return (bool) $document->getAttribute('is_cancelled');
    }

    private function normaliseText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
