@extends('layouts.user')
@section('page-title', 'Edit Profil')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 space-y-5">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Profil</h1>
        <p class="text-gray-500 text-sm mt-1">Perbarui informasi akun Anda.</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        @include('profile.partials.update-profile-information-form')
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        @include('profile.partials.update-password-form')
    </div>

    <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-6">
        @include('profile.partials.delete-user-form')
    </div>

</div>
@endsection
