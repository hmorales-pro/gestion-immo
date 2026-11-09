<?php

namespace App\Livewire\Forms;

use App\Enums\EmploymentStatus;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProspectForm extends Form
{
    public ?int $slotId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $employmentStatus = '';

    public string $message = '';

    /** Honeypot: must stay empty. */
    public string $website = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slotId' => ['required', 'integer'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'max:190', 'email:rfc,strict,filter'],
            'phone' => ['required', 'string', 'max:32', 'phone:FR,INTERNATIONAL,mobile,fixed_line,fixed_line_or_mobile'],
            'employmentStatus' => ['required', Rule::enum(EmploymentStatus::class)],
            'message' => ['nullable', 'string', 'max:1000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slotId.required' => 'Choisissez un créneau.',
            'phone.phone' => 'Ce numéro de téléphone ne semble pas valide.',
            'email.email' => 'Cette adresse e-mail ne semble pas valide.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'e-mail',
            'phone' => 'téléphone',
            'employmentStatus' => 'situation professionnelle',
            'message' => 'message',
        ];
    }

    /**
     * @return array{prospect_name: string, prospect_email: string, prospect_phone: string, employment_status: string, message: string|null}
     */
    public function toBookingData(): array
    {
        return [
            'prospect_name' => trim($this->name),
            'prospect_email' => $this->email,
            'prospect_phone' => $this->phone,
            'employment_status' => $this->employmentStatus,
            'message' => filled($this->message) ? trim($this->message) : null,
        ];
    }
}
