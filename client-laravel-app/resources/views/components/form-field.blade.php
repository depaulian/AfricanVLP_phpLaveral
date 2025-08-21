{{-- Accessible form field component for client application --}}
@props([
    'name' => '',
    'label' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'help' => '',
    'options' => [], // for select fields
    'rows' => 3, // for textarea
    'min' => null,
    'max' => null,
    'minlength' => null,
    'maxlength' => null,
    'pattern' => null,
    'patternMessage' => null,
    'autocomplete' => null,
    'multiple' => false,
    'accept' => null, // for file inputs
])

@php
    $fieldId = $name . '_' . uniqid();
    $errorId = $name . '-error';
    $helpId = $help ? $name . '-help' : null;
    $describedBy = collect([$errorId, $helpId])->filter()->implode(' ');
@endphp

<div class="form-group">
    {{-- Label --}}
    @if($label)
    <label 
        for="{{ $fieldId }}" 
        class="form-label {{ $required ? 'required' : '' }}"
    >
        {{ $label }}
        @if($required)
            <span class="text-red-500" aria-label="required">*</span>
        @endif
    </label>
    @endif
    
    {{-- Input field --}}
    @if($type === 'textarea')
        <textarea
            id="{{ $fieldId }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            class="form-input"
            placeholder="{{ $placeholder }}"
            aria-describedby="{{ $describedBy }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $minlength ? "minlength={$minlength}" : '' }}
            {{ $maxlength ? "maxlength={$maxlength}" : '' }}
            {{ $autocomplete ? "autocomplete={$autocomplete}" : '' }}
            x-bind:class="getFieldClass('{{ $name }}')"
            aria-invalid="false"
        >{{ old($name, $value) }}</textarea>
    @elseif($type === 'select')
        <select
            id="{{ $fieldId }}"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            class="form-input"
            aria-describedby="{{ $describedBy }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $multiple ? 'multiple' : '' }}
            x-bind:class="getFieldClass('{{ $name }}')"
            aria-invalid="false"
        >
            @if(!$multiple && !$required)
                <option value="">{{ $placeholder ?: 'Select an option' }}</option>
            @endif
            @foreach($options as $optionValue => $optionLabel)
                <option 
                    value="{{ $optionValue }}" 
                    {{ (old($name, $value) == $optionValue) ? 'selected' : '' }}
                >
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    @elseif($type === 'file')
        <input
            type="file"
            id="{{ $fieldId }}"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            class="form-input"
            aria-describedby="{{ $describedBy }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $multiple ? 'multiple' : '' }}
            {{ $accept ? "accept={$accept}" : '' }}
            x-bind:class="getFieldClass('{{ $name }}')"
            aria-invalid="false"
        >
    @elseif($type === 'checkbox')
        <div class="flex items-center">
            <input
                type="checkbox"
                id="{{ $fieldId }}"
                name="{{ $name }}"
                value="1"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                aria-describedby="{{ $describedBy }}"
                {{ $required ? 'required' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                {{ old($name, $value) ? 'checked' : '' }}
                x-bind:class="getFieldClass('{{ $name }}')"
                aria-invalid="false"
            >
            @if($label)
            <label for="{{ $fieldId }}" class="ml-2 block text-sm text-gray-900">
                {{ $label }}
                @if($required)
                    <span class="text-red-500" aria-label="required">*</span>
                @endif
            </label>
            @endif
        </div>
    @elseif($type === 'radio')
        <fieldset class="space-y-2" role="radiogroup" aria-labelledby="{{ $fieldId }}-legend">
            @if($label)
            <legend id="{{ $fieldId }}-legend" class="form-label {{ $required ? 'required' : '' }}">
                {{ $label }}
                @if($required)
                    <span class="text-red-500" aria-label="required">*</span>
                @endif
            </legend>
            @endif
            @foreach($options as $optionValue => $optionLabel)
                @php $radioId = $fieldId . '_' . $loop->index; @endphp
                <div class="flex items-center">
                    <input
                        type="radio"
                        id="{{ $radioId }}"
                        name="{{ $name }}"
                        value="{{ $optionValue }}"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                        {{ $required ? 'required' : '' }}
                        {{ $disabled ? 'disabled' : '' }}
                        {{ (old($name, $value) == $optionValue) ? 'checked' : '' }}
                        aria-describedby="{{ $describedBy }}"
                        x-bind:class="getFieldClass('{{ $name }}')"
                        aria-invalid="false"
                    >
                    <label for="{{ $radioId }}" class="ml-2 block text-sm text-gray-900">
                        {{ $optionLabel }}
                    </label>
                </div>
            @endforeach
        </fieldset>
    @else
        <input
            type="{{ $type }}"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            class="form-input"
            placeholder="{{ $placeholder }}"
            aria-describedby="{{ $describedBy }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $min !== null ? "min={$min}" : '' }}
            {{ $max !== null ? "max={$max}" : '' }}
            {{ $minlength ? "minlength={$minlength}" : '' }}
            {{ $maxlength ? "maxlength={$maxlength}" : '' }}
            {{ $pattern ? "pattern={$pattern}" : '' }}
            {{ $patternMessage ? "data-pattern-message={$patternMessage}" : '' }}
            {{ $autocomplete ? "autocomplete={$autocomplete}" : '' }}
            @if($type === 'password')
                data-password="true"
            @elseif($name === 'password_confirmation')
                data-confirm-password="true"
            @elseif($type === 'tel')
                data-phone="true"
            @endif
            x-bind:class="getFieldClass('{{ $name }}')"
            aria-invalid="false"
        >
    @endif
    
    {{-- Help text --}}
    @if($help)
    <div id="{{ $helpId }}" class="form-help">
        {{ $help }}
    </div>
    @endif
    
    {{-- Error container --}}
    <div id="{{ $errorId }}" class="form-error" role="alert" aria-live="polite">
        {{-- Error messages will be populated by JavaScript --}}
    </div>
    
    {{-- Server-side validation errors --}}
    @error($name)
    <div class="form-error" role="alert">
        {{ $message }}
    </div>
    @enderror
</div>