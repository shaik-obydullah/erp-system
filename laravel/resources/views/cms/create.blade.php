@extends('roles.layout')

@section('title', 'Add Content')

@section('content')
<div class="card" x-data="formHandler('{{ route('cms.store') }}', 'POST')" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Add New Content</h2>
        <a href="{{ route('cms.index') }}" class="btn btn-secondary">Back to CMS</a>
    </div>
    <div class="card-body">

        <div x-show="successMessage" x-cloak class="alert alert-success show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span x-text="successMessage"></span>
        </div>

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span x-text="errorMessage"></span>
        </div>

        <form @submit.prevent="submit($event)">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" x-model="form.name" required>
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
                <div class="form-group">
                    <label for="type">Type *</label>
                    <select id="type" x-model="form.type" required>
                        <option value="hero">Hero Banner</option>
                        <option value="banner">Banner</option>
                        <option value="page">Page</option>
                        <option value="faq">FAQ</option>
                        <option value="settings">Settings</option>
                    </select>
                    <span class="form-error" x-show="errors.type" x-text="errors.type"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" x-model="form.slug" placeholder="auto-generated from name if empty">
                    <span class="form-error" x-show="errors.slug" x-text="errors.slug"></span>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" x-model="form.sort_order" min="0">
                    <span class="form-error" x-show="errors.sort_order" x-text="errors.sort_order"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="media">Media URL</label>
                    <input type="text" id="media" x-model="form.media" placeholder="Image URL or path">
                    <span class="form-error" x-show="errors.media" x-text="errors.media"></span>
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" x-model="form.status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="attribute">Attribute / Subtitle</label>
                    <input type="text" id="attribute" x-model="form.attribute" placeholder="Short subtitle or meta info">
                    <span class="form-error" x-show="errors.attribute" x-text="errors.attribute"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" x-model="form.content" rows="8" placeholder="Main content body..."></textarea>
                    <span class="form-error" x-show="errors.content" x-text="errors.content"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Content</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('cms.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function formHandler(url, method) {
        return {
            form: {
                name: '',
                type: 'page',
                slug: '',
                media: '',
                content: '',
                attribute: '',
                status: 'active',
                sort_order: 0,
            },
            errors: {},
            errorMessage: '',
            successMessage: '',
            submitting: false,
            csrfToken: '',
            init() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            },
            async submit() {
                this.errors = {};
                this.errorMessage = '';
                this.successMessage = '';
                this.submitting = true;
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify(this.form),
                    });
                    const data = await response.json();
                    if (response.ok) { window.location.href = data.redirect || '{{ route("cms.index") }}'; return; }
                    if (data.errors) { this.errors = {}; for (const key in data.errors) { this.errors[key] = data.errors[key][0]; } }
                    else if (data.message) { this.errorMessage = data.message; }
                } catch (e) { this.errorMessage = 'An unexpected error occurred.'; } finally { this.submitting = false; }
            },
        };
    }
</script>
@endsection
