@extends('front.layouts.app')

@section('title', 'Acheter des crédits')

@section('content')
<section style="padding:60px 20px;background:#f8fafc;min-height:70vh;">
    <div style="max-width:900px;margin:auto;background:white;border-radius:20px;padding:30px;box-shadow:0 15px 45px rgba(15,23,42,.10);">
        <h1 style="font-size:30px;font-weight:800;margin-bottom:10px;">
            Acheter des crédits
        </h1>

        <p style="color:#64748b;margin-bottom:25px;">
            Votre compte est actif, mais vous n’avez pas encore de crédits disponibles.
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;">
            <div style="border:1px solid #e2e8f0;border-radius:16px;padding:20px;">
                <h3>Pack Découverte</h3>
                <p><strong>10 crédits</strong></p>
                <p>Pour tester le moteur.</p>
                <button style="background:#0053b3;color:white;border:none;padding:10px 16px;border-radius:10px;">
                    Acheter
                </button>
            </div>

            <div style="border:1px solid #e2e8f0;border-radius:16px;padding:20px;">
                <h3>Pack Pro</h3>
                <p><strong>100 crédits</strong></p>
                <p>Pour un usage régulier.</p>
                <button style="background:#0053b3;color:white;border:none;padding:10px 16px;border-radius:10px;">
                    Acheter
                </button>
            </div>

            <div style="border:1px solid #e2e8f0;border-radius:16px;padding:20px;">
                <h3>Pack Business</h3>
                <p><strong>500 crédits</strong></p>
                <p>Pour équipes et prospection.</p>
                <button style="background:#0053b3;color:white;border:none;padding:10px 16px;border-radius:10px;">
                    Acheter
                </button>
            </div>
        </div>
    </div>
</section>
@endsection