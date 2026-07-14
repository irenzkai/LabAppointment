@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="col-md-6 text-center">
        <div class="card p-5 border-neon bg-black shadow-lg">
            
            {{-- Icon Header --}}
            <div class="mb-4">
                <div class="display-1 text-neon mb-3">
                    <i class="bi bi-envelope-check"></i>
                </div>
                <h2 class="text-white fw-bold mb-1 uppercase tracking-tighter">Verify Your Email</h2>
                <p class="text-secondary small uppercase fw-bold">Step 5: Final Account Activation</p>
            </div>

            {{-- Status Message --}}
            @if (session('status') == 'verification-link-sent')
                <div class="alert bg-neon text-dark fw-bold small mb-4 shadow-neon">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    A new verification link has been sent to the email address you provided.
                </div>
            @endif

            <div class="text-start mb-4">
                <p class="text-white">
                    Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? 
                </p>
                <p class="text-secondary small italic">
                    If you didn't receive the email, we will gladly send you another.
                </p>
            </div>

            {{-- Resend Button --}}
            <div class="d-grid gap-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn-custom btn-neon w-100 py-3 fw-bold shadow-sm">
                        RESEND VERIFICATION EMAIL
                    </button>
                </form>

                <hr class="border-secondary border-opacity-25 my-2">

                {{-- Logout Option --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link text-secondary text-decoration-none small hover-neon">
                        <i class="bi bi-box-arrow-left me-2"></i>Logout and try again later
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .shadow-neon {
        box-shadow: 0 0 15px var(--neon);
    }
    .hover-neon:hover {
        color: var(--neon) !important;
    }
</style>
@endsection