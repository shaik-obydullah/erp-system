<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ERP Admin') }} - Profile</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
        <script src="/js/app.js?v={{ md5_file(public_path('js/app.js')) }}"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="dashboard-body">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        @include('layouts.sidebar')

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <button class="menu-toggle" id="menuToggle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="page-title">Profile</h1>
                @if(session('success'))
                <div class="header-flash" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)">
                    <div class="alert alert-success">{{ session('success') }}</div>
                </div>
                @endif
                @include('layouts.header-actions')
            </header>

            <div class="profile-content">
                <!-- Profile Information -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h2>Profile Information</h2>
                        <p>Update your account's profile information and email address.</p>
                    </div>
                    <div class="profile-card-body">
                        <div class="profile-avatar-section">
                            <div class="profile-avatar">{{ substr($user->first_name, 0, 1) }}</div>
                            <div class="profile-avatar-info">
                                <h3>{{ $user->first_name }} {{ $user->last_name }}</h3>
                                <p>{{ $user->email }}</p>
                                <p style="font-size: 12px; color: var(--text-disabled); margin-top: 4px;">Member since {{ $user->created_at ? $user->created_at->format('M Y') : 'N/A' }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf
                            @method('patch')

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required autofocus>
                                    @error('first_name') <span class="error-text">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}">
                                    @error('last_name') <span class="error-text">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    @error('email') <span class="error-text">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group">
                                    <label for="mobile">Mobile</label>
                                    <input type="text" id="mobile" name="mobile" value="{{ old('mobile', $user->mobile) }}">
                                    @error('mobile') <span class="error-text">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" value="{{ old('address', $user->address) }}">
                                @error('address') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Update Password -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h2>Update Password</h2>
                        <p>Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                    <div class="profile-card-body">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('put')

                            <div class="form-group password-toggle" x-data="{ show: false }">
                                <label for="current_password">Current Password</label>
                                <input :type="show ? 'text' : 'password'" id="current_password" name="current_password" autocomplete="current-password">
                                <button type="button" class="password-toggle-btn" @click="show = !show" x-text="show ? 'Hide' : 'Show'"></button>
                                @error('current_password', 'updatePassword') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group password-toggle" x-data="{ show: false }">
                                <label for="password">New Password</label>
                                <input :type="show ? 'text' : 'password'" id="password" name="password" autocomplete="new-password">
                                <button type="button" class="password-toggle-btn" @click="show = !show" x-text="show ? 'Hide' : 'Show'"></button>
                                @error('password', 'updatePassword') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group password-toggle" x-data="{ show: false }">
                                <label for="password_confirmation">Confirm Password</label>
                                <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                                <button type="button" class="password-toggle-btn" @click="show = !show" x-text="show ? 'Hide' : 'Show'"></button>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Account -->
                <div class="profile-card">
                    <div class="profile-card-body">
                        <div class="delete-section">
                            <h3>Delete Account</h3>
                            <p>Once your account is deleted, all of its resources and data will be permanently deleted.</p>
                            <button class="btn btn-danger" @click="$dispatch('open-modal', 'confirm-user-deletion')">Delete Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Delete Account Modal -->
        <div class="modal-overlay" id="deleteModal" x-data="{ show: false }" x-show="show" x-transition @open-modal.window="show = true" @close.window="show = false" style="display: none;">
            <div class="modal" @click.stop>
                <h2>Are you sure you want to delete your account?</h2>
                <p>Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm.</p>

                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="form-group">
                        <input type="password" name="password" placeholder="Enter your password" required>
                        @error('password', 'userDeletion') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="show = false">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Account</button>
                    </div>
                </form>
            </div>
        </div>

        @include('layouts.header-actions-js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const menuToggle = document.getElementById('menuToggle');
                const sidebarClose = document.getElementById('sidebarClose');
                const sidebarOverlay = document.getElementById('sidebarOverlay');

                menuToggle.addEventListener('click', () => { sidebar.classList.add('open'); sidebarOverlay.classList.add('show'); });
                sidebarClose.addEventListener('click', closeSidebar);
                sidebarOverlay.addEventListener('click', closeSidebar);
                function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); }
            });
        </script>
    </body>
</html>
