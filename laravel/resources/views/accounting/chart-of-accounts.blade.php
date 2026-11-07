@extends('roles.layout')

@section('title', 'Accounting — Chart of Accounts')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2 class="card-title">Chart of Accounts</h2>
        <a href="{{ route('accounting.index') }}" class="btn btn-secondary">Back to Accounting</a>
    </div>

    @if(auth('admin')->user()?->hasPermission('accounting.manage'))
        <details style="padding:16px; border-bottom:1px solid var(--border);">
            <summary style="cursor:pointer; font-weight:700;">Add a sub-account</summary>
            <form method="POST" action="{{ route('accounting.accounts.store') }}" style="margin-top:16px; display:grid; grid-template-columns:repeat(5, minmax(0, 1fr)); gap:12px; align-items:end;">
                @csrf
                <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">Code
                    <input type="text" name="code" value="{{ old('code') }}" class="form-input" placeholder="e.g. 5210" required>
                </label>
                <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">Friendly name
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. Office supplies" required>
                </label>
                <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">Type
                    <select name="type" class="form-input" required>
                        @foreach(['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">Parent (optional)
                    <select name="parent_id" class="form-input">
                        <option value="">— None —</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id') === (string) $parent->id)>{{ $parent->code }} — {{ $parent->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn btn-primary">Add account</button>
                <label style="grid-column:1 / -1; display:flex; flex-direction:column; gap:4px; font-size:12px;">Description
                    <input type="text" name="description" value="{{ old('description') }}" class="form-input" placeholder="Plain-language explanation for normal users">
                </label>
            </form>
        </details>
    @endif

    @php
        $labels = [
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
            'revenue' => 'Revenue',
            'expense' => 'Expenses',
        ];
    @endphp

    @foreach($labels as $type => $label)
        <div style="padding:16px 16px 4px;">
            <h3 style="font-weight:700; margin-bottom:8px;">{{ $label }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Normal balance</th>
                        <th>System</th>
                        <th>Status</th>
                        @if(auth('admin')->user()?->hasPermission('accounting.manage'))
                            <th style="text-align:right;">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse(($accounts[$type] ?? collect()) as $account)
                        <tr>
                            <td>{{ $account->code }}</td>
                            <td>
                                {{ $account->name }}
                                @if($account->parent)
                                    <div style="font-size:12px; color:var(--text-secondary);">Sub-account of {{ $account->parent->name }}</div>
                                @endif
                            </td>
                            <td style="text-transform:capitalize;">{{ $account->normal_balance }}</td>
                            <td>{{ $account->is_system ? 'Default' : 'Custom' }}</td>
                            <td style="color:{{ $account->is_active ? '#15803d' : '#b91c1c' }};">{{ $account->is_active ? 'Active' : 'Inactive' }}</td>
                            @if(auth('admin')->user()?->hasPermission('accounting.manage'))
                                <td style="text-align:right; white-space:nowrap;">
                                    <details style="display:inline-block; text-align:left;">
                                        <summary class="btn btn-secondary" style="display:inline-block; cursor:pointer; padding:6px 10px;">Edit</summary>
                                        <form method="POST" action="{{ route('accounting.accounts.update', $account) }}" style="position:absolute; z-index:20; background:var(--card-bg, #fff); border:1px solid var(--border); border-radius:8px; padding:12px; margin-top:6px; width:320px; display:grid; gap:8px;">
                                            @csrf
                                            @method('PUT')
                                            <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">Code
                                                <input type="text" name="code" value="{{ $account->code }}" class="form-input" required>
                                            </label>
                                            <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">Name
                                                <input type="text" name="name" value="{{ $account->name }}" class="form-input" required>
                                            </label>
                                            <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">Type
                                                <select name="type" class="form-input" @disabled($account->is_system)>
                                                    @foreach(['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense'] as $value => $typeLabel)
                                                        <option value="{{ $value }}" @selected($account->type === $value)>{{ $typeLabel }}</option>
                                                    @endforeach
                                                </select>
                                                @if($account->is_system)
                                                    <input type="hidden" name="type" value="{{ $account->type }}">
                                                @endif
                                            </label>
                                            <label style="display:flex; flex-direction:column; gap:4px; font-size:12px;">Description
                                                <input type="text" name="description" value="{{ $account->description }}" class="form-input">
                                            </label>
                                            <label style="display:flex; align-items:center; gap:8px; font-size:12px;">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" @checked($account->is_active)> Active
                                            </label>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </form>
                                    </details>

                                    @unless($account->is_system)
                                        <form method="POST" action="{{ route('accounting.accounts.destroy', $account) }}" style="display:inline-block;" onsubmit="return confirm('Delete this account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary" style="padding:6px 10px;">Delete</button>
                                        </form>
                                    @endunless
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No accounts in this group.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@endsection
