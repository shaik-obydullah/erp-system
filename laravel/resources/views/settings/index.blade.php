@extends('roles.layout')

@section('title', 'ERP Settings')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">ERP Settings</h2>
    </div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $error)
                <span>{{ $error }}</span><br>
            @endforeach
        </div>
        @endif

        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <h3 style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">Company Information</h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="company_name">Company Name *</label>
                    <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" required>
                </div>
                <div class="form-group">
                    <label for="company_email">Company Email *</label>
                    <input type="email" id="company_email" name="company_email" value="{{ old('company_email', $settings['company_email']) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="company_phone">Phone</label>
                    <input type="text" id="company_phone" name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}">
                </div>
                <div class="form-group">
                    <label for="company_website">Website</label>
                    <input type="url" id="company_website" name="company_website" value="{{ old('company_website', $settings['company_website']) }}" placeholder="https://example.com">
                </div>
            </div>

            <div class="form-group">
                <label for="company_address">Address</label>
                <input type="text" id="company_address" name="company_address" value="{{ old('company_address', $settings['company_address']) }}">
            </div>

            <h3 style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">Financial Settings</h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="currency">Currency *</label>
                    <input type="text" id="currency" name="currency" value="{{ old('currency', $settings['currency']) }}" required>
                </div>
                <div class="form-group">
                    <label for="currency_symbol">Currency Symbol *</label>
                    <input type="text" id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tax_rate">Tax Rate (%) *</label>
                    <input type="number" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $settings['tax_rate']) }}" min="0" max="100" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="fiscal_year_start">Fiscal Year Start *</label>
                    <input type="text" id="fiscal_year_start" name="fiscal_year_start" value="{{ old('fiscal_year_start', $settings['fiscal_year_start']) }}" placeholder="MM-DD" required>
                </div>
            </div>

            <h3 style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">General Settings</h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="timezone">Timezone *</label>
                    <select id="timezone" name="timezone" required>
                        @php
                        $timezones = ['UTC','America/New_York','America/Chicago','America/Denver','America/Los_Angeles','Europe/London','Europe/Paris','Europe/Berlin','Asia/Dubai','Asia/Kolkata','Asia/Shanghai','Asia/Tokyo','Australia/Sydney','Pacific/Auckland'];
                        @endphp
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}" {{ old('timezone', $settings['timezone']) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_format">Date Format *</label>
                    <select id="date_format" name="date_format" required>
                        @php
                        $formats = ['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'd-m-Y' => 'DD-MM-YYYY'];
                        @endphp
                        @foreach($formats as $val => $lbl)
                            <option value="{{ $val }}" {{ old('date_format', $settings['date_format']) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="invoice_prefix">Invoice Prefix</label>
                    <input type="text" id="invoice_prefix" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}">
                </div>
                <div class="form-group">
                    <label for="order_prefix">Order Prefix</label>
                    <input type="text" id="order_prefix" name="order_prefix" value="{{ old('order_prefix', $settings['order_prefix']) }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="low_stock_threshold">Low Stock Threshold</label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" min="0">
                </div>
                <div class="form-group">
                    <label for="allow_registration">Allow Registration *</label>
                    <select id="allow_registration" name="allow_registration" required>
                        <option value="1" {{ old('allow_registration', $settings['allow_registration']) == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('allow_registration', $settings['allow_registration']) == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h2 class="card-title">Maintenance Mode</h2>
    </div>
    <div class="card-body">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border-radius: 8px; {{ $isMaintenance ? 'background: #fef7e0; border: 1px solid #fde293;' : 'background: #e6f4ea; border: 1px solid #ceead6;' }}">
            <div style="display: flex; align-items: center; gap: 10px;">
                @if($isMaintenance)
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e37400" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span style="font-size: 14px; font-weight: 500; color: #e37400;">Site is in <strong>maintenance mode</strong></span>
                @else
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1e8e3e" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span style="font-size: 14px; font-weight: 500; color: #1e8e3e;">Site is <strong>live</strong></span>
                @endif
            </div>
            <form action="{{ route('settings.maintenance.toggle') }}" method="POST" style="margin: 0;">
                @csrf
                <input type="hidden" name="action" value="{{ $isMaintenance ? 'off' : 'on' }}">
                <button type="submit" class="btn {{ $isMaintenance ? 'btn-primary' : 'btn-danger' }} btn-sm">
                    {{ $isMaintenance ? 'Turn Off' : 'Turn On' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
