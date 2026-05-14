@extends('front.layouts.app')

@section('title', 'Acheter des crédits')

@section('content')

    @php
        $user = auth()->user();

        $packs = [
            [
                'key' => 'decouverte',
                'name' => 'Pack Découverte',
                'credits' => 10,
                'price' => 49,
                'description' => 'Pour tester le moteur Data Rocket.',
                'icon' => 'fa-rocket',
                'color' => '#0053b3',
                'recommended' => false,
            ],
            [
                'key' => 'pro',
                'name' => 'Pack Pro',
                'credits' => 100,
                'price' => 399,
                'description' => 'Pour un usage régulier et professionnel.',
                'icon' => 'fa-briefcase',
                'color' => '#7c3aed',
                'recommended' => true,
            ],
            [
                'key' => 'business',
                'name' => 'Pack Business',
                'credits' => 500,
                'price' => 1499,
                'description' => 'Pour équipes, prospection et volume.',
                'icon' => 'fa-building',
                'color' => '#0f172a',
                'recommended' => false,
            ],
        ];
    @endphp

    <style>
        .credits-page {
            min-height: 100vh;
            background: #f8fafc;
            padding: 45px 18px
        }

        .credits-container {
            max-width: 1180px;
            margin: 0 auto
        }

        .credits-hero {
            background: linear-gradient(135deg, #0f172a, #0053b3);
            color: white;
            border-radius: 30px;
            padding: 34px;
            margin-bottom: 24px
        }

        .credits-hero h1 {
            margin: 0;
            font-size: 38px;
            font-weight: 950
        }

        .credits-hero p {
            color: rgba(255, 255, 255, .78);
            margin: 10px 0 0;
            line-height: 1.6
        }

        .pack-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 26px
        }

        .pack-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 24px;
            cursor: pointer;
            transition: .25s;
            position: relative;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .05)
        }

        .pack-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 22px 55px rgba(15, 23, 42, .12);
            border-color: #0053b3
        }

        .pack-card.active {
            border: 2px solid #0053b3;
            box-shadow: 0 24px 60px rgba(0, 83, 179, .18)
        }

        .pack-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #dcfce7;
            color: #166534;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900
        }

        .pack-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: white;
            font-size: 22px;
            margin-bottom: 18px
        }

        .pack-card h3 {
            margin: 0;
            font-size: 21px;
            color: #0f172a;
            font-weight: 950
        }

        .pack-desc {
            color: #64748b;
            line-height: 1.5;
            margin: 10px 0 18px
        }

        .pack-price {
            font-size: 32px;
            font-weight: 950;
            color: #0f172a
        }

        .pack-credits {
            color: #0053b3;
            font-weight: 950;
            margin-top: 4px
        }

        .buy-section {
            display: none;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 26px;
            padding: 28px;
            box-shadow: 0 16px 45px rgba(15, 23, 42, .07);
            margin-bottom: 20px
        }

        .buy-section.active {
            display: block
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 18px
        }

        .section-header h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 950;
            color: #0f172a
        }

        .section-header p {
            margin: 8px 0 0;
            color: #64748b
        }

        .progress-steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 26px
        }

        .step {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 16px
        }

        .step.active {
            border-color: #0053b3;
            background: #eff6ff
        }

        .step.done {
            border-color: #22c55e;
            background: #dcfce7
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #e2e8f0;
            color: #334155;
            font-weight: 950;
            margin-bottom: 10px
        }

        .step.active .step-number {
            background: #0053b3;
            color: white
        }

        .step.done .step-number {
            background: #16a34a;
            color: white
        }

        .step-title {
            font-weight: 950;
            color: #0f172a;
            font-size: 14px
        }

        .step-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.4
        }

        .buy-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 22px
        }

        .form-panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 900;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase
        }

        .form-group input,
        .form-group select {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            outline: none;
            background: white
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #0053b3;
            box-shadow: 0 0 0 4px rgba(0, 83, 179, .10)
        }

        .summary-card {
            background: #0f172a;
            color: white;
            border-radius: 22px;
            padding: 22px;
            height: max-content
        }

        .summary-card h3 {
            margin: 0 0 16px;
            font-size: 20px;
            font-weight: 950
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, .12);
            color: rgba(255, 255, 255, .78)
        }

        .summary-row strong {
            color: white
        }

        .btn {
            border: none;
            border-radius: 14px;
            padding: 13px 18px;
            font-weight: 950;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px
        }

        .btn-primary {
            background: #0053b3;
            color: white
        }

        .btn-primary:hover {
            background: #003d85;
            color: white
        }

        .btn-gray {
            background: #f1f5f9;
            color: #334155
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px
        }

        /* Formulaire carte bancaire */
        .card-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .card-field {
            background: white;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            transition: all 0.2s;
        }

        .card-field:focus-within {
            border-color: #0053b3;
            box-shadow: 0 0 0 3px rgba(0, 83, 179, 0.1);
        }

        .card-field label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        .card-field input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 14px;
            background: transparent;
        }

        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #dcfce7;
            color: #166534;
            padding: 8px 12px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        @media(max-width:950px) {

            .pack-grid,
            .buy-layout,
            .progress-steps {
                grid-template-columns: 1fr
            }

            .section-header {
                flex-direction: column
            }

            .card-row {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:620px) {
            .credits-hero h1 {
                font-size: 28px
            }

            .form-grid {
                grid-template-columns: 1fr
            }

            .credits-page {
                padding: 24px 12px
            }
        }
    </style>

    <section class="credits-page">
        <div class="credits-container">

            <div class="credits-hero">
                <h1>Acheter des crédits</h1>
                <p>
                    Choisissez un pack, vérifiez vos informations, confirmez la commande puis procédez au paiement par carte bancaire.
                </p>
            </div>

            <div class="pack-grid">
                @foreach($packs as $pack)
                    <div class="pack-card {{ $pack['recommended'] ? 'active' : '' }}" data-pack="{{ $pack['key'] }}">
                        @if($pack['recommended'])
                            <div class="pack-badge">Recommandé</div>
                        @endif

                        <div class="pack-icon" style="background:{{ $pack['color'] }}">
                            <i class="fa-solid {{ $pack['icon'] }}"></i>
                        </div>

                        <h3>{{ $pack['name'] }}</h3>
                        <p class="pack-desc">{{ $pack['description'] }}</p>

                        <div class="pack-price">{{ number_format($pack['price'], 0, ',', ' ') }} DH</div>
                        <div class="pack-credits">{{ $pack['credits'] }} crédits inclus</div>
                    </div>
                @endforeach
            </div>

            @foreach($packs as $pack)
                <section class="buy-section {{ $pack['recommended'] ? 'active' : '' }}" id="buy-{{ $pack['key'] }}">
                    <div class="section-header">
                        <div>
                            <h2>{{ $pack['name'] }}</h2>
                            <p>{{ $pack['credits'] }} crédits pour {{ number_format($pack['price'], 0, ',', ' ') }} DH.</p>
                        </div>

                        <button class="btn btn-gray" type="button" onclick="resetPackSelection()">
                            Changer de pack
                        </button>
                    </div>

                    <div class="progress-steps">
                        <div class="step done">
                            <div class="step-number"><i class="fa-solid fa-check"></i></div>
                            <div class="step-title">Compte identifié</div>
                            <div class="step-text">{{ $user->name ?? 'Utilisateur connecté' }}</div>
                        </div>

                        <div class="step active">
                            <div class="step-number">2</div>
                            <div class="step-title">Informations</div>
                            <div class="step-text">Vérifiez vos coordonnées.</div>
                        </div>

                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-title">Confirmation</div>
                            <div class="step-text">Validez le pack choisi.</div>
                        </div>

                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-title">Paiement</div>
                            <div class="step-text">Finalisez l’achat par carte.</div>
                        </div>
                    </div>

                    <div class="buy-layout">
                        <div class="form-panel" id="formPanel-{{ $pack['key'] }}">
                            <form id="paymentForm-{{ $pack['key'] }}" method="POST" action="{{ route('payment.process') }}">
                                @csrf

                                <input type="hidden" name="pack_key" value="{{ $pack['key'] }}">
                                <input type="hidden" name="credits" value="{{ $pack['credits'] }}">
                                <input type="hidden" name="amount" value="{{ $pack['price'] }}">

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Nom complet</label>
                                        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Téléphone</label>
                                        <input type="tel" name="phone" value="{{ old('phone', $user->phone ?? '') }}" placeholder="+212 6XX XXX XXX">
                                    </div>

                                    <div class="form-group">
                                        <label>Adresse (optionnelle)</label>
                                        <input type="text" name="address" placeholder="Adresse de facturation">
                                    </div>
                                </div>

                                <div class="actions">
                                    <button type="button" class="btn btn-primary" onclick="goToConfirmation('{{ $pack['key'] }}')">
                                        Continuer vers confirmation
                                    </button>

                                    <a href="{{ route('dashboard') }}" class="btn btn-gray">
                                        Retour à mon espace
                                    </a>
                                </div>
                            </form>
                        </div>

                        <aside class="summary-card">
                            <h3>Résumé de commande</h3>

                            <div class="summary-row">
                                <span>Pack</span>
                                <strong>{{ $pack['name'] }}</strong>
                            </div>

                            <div class="summary-row">
                                <span>Crédits</span>
                                <strong>{{ $pack['credits'] }}</strong>
                            </div>

                            <div class="summary-row">
                                <span>Montant</span>
                                <strong>{{ number_format($pack['price'], 0, ',', ' ') }} DH</strong>
                            </div>

                            <div class="summary-row">
                                <span>Compte</span>
                                <strong>{{ $user->email ?? '-' }}</strong>
                            </div>

                            <div style="margin-top:18px;color:rgba(255,255,255,.72);font-size:13px;line-height:1.6;">
                                Après validation, les crédits seront ajoutés à votre compte.
                            </div>
                        </aside>
                    </div>
                </section>
            @endforeach

        </div>
    </section>

    <script>
        let currentPack = null;

        document.querySelectorAll('.pack-card').forEach(card => {
            card.addEventListener('click', function () {
                const pack = this.dataset.pack;
                currentPack = pack;

                document.querySelectorAll('.pack-card').forEach(c => c.classList.remove('active'));
                document.querySelectorAll('.buy-section').forEach(s => s.classList.remove('active'));

                this.classList.add('active');

                const section = document.getElementById('buy-' + pack);

                if (section) {
                    section.classList.add('active');

                    setTimeout(() => {
                        section.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                }
            });
        });

        function goToConfirmation(pack) {
            const section = document.getElementById('buy-' + pack);

            if (!section) return;

            const steps = section.querySelectorAll('.step');

            if (steps[1]) {
                steps[1].classList.remove('active');
                steps[1].classList.add('done');
                steps[1].querySelector('.step-number').innerHTML = '<i class="fa-solid fa-check"></i>';
            }

            if (steps[2]) {
                steps[2].classList.add('active');
            }

            const formPanel = section.querySelector('.form-panel');

            if (formPanel) {
                formPanel.innerHTML = `
                    <h3 style="margin:0 0 12px;font-size:22px;font-weight:950;color:#0f172a;">
                        Confirmation de votre achat
                    </h3>

                    <p style="color:#64748b;line-height:1.6;margin-bottom:20px;">
                        Vérifiez le résumé de votre commande à droite, puis confirmez pour passer au paiement par carte bancaire.
                    </p>

                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-primary" onclick="goToCardPayment('${pack}')">
                            Confirmer et payer par carte
                        </button>

                        <button type="button" class="btn btn-gray" onclick="location.reload()">
                            Modifier mes informations
                        </button>
                    </div>
                `;
            }
        }

        function goToCardPayment(pack) {
            const section = document.getElementById('buy-' + pack);

            if (!section) return;

            const steps = section.querySelectorAll('.step');

            if (steps[2]) {
                steps[2].classList.remove('active');
                steps[2].classList.add('done');
                steps[2].querySelector('.step-number').innerHTML = '<i class="fa-solid fa-check"></i>';
            }

            if (steps[3]) {
                steps[3].classList.add('active');
            }

            const formPanel = section.querySelector('.form-panel');

            if (formPanel) {
                formPanel.innerHTML = `
                    <h3 style="margin:0 0 16px;font-size:22px;font-weight:950;color:#0f172a;">
                        💳 Paiement par carte bancaire
                    </h3>

                    <div class="secure-badge">
                        <i class="fa-solid fa-lock"></i>
                        Paiement 100% sécurisé
                    </div>

                    <div class="card-form">
                        <div class="card-field">
                            <label>Numéro de carte</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" autocomplete="off">
                        </div>

                        <div class="card-row">
                            <div class="card-field">
                                <label>Date d'expiration</label>
                                <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/AA" maxlength="5" autocomplete="off">
                            </div>

                            <div class="card-field">
                                <label>CVV</label>
                                <input type="text" id="card_cvv" name="card_cvv" placeholder="123" maxlength="4" autocomplete="off">
                            </div>
                        </div>

                        <div class="card-field">
                            <label>Nom du titulaire</label>
                            <input type="text" id="card_holder" name="card_holder" placeholder="Nom PRENOM" autocomplete="off">
                        </div>
                    </div>

                    <div style="margin-top: 24px;">
                        <button type="button" class="btn btn-primary" onclick="submitCardPayment('${pack}')" style="width:100%;">
                            <i class="fa-solid fa-credit-card"></i> Payer maintenant
                        </button>
                    </div>

                    <p style="text-align:center;font-size:12px;color:#64748b;margin-top:16px;">
                        Les transactions sont sécurisées par chiffrement SSL.
                    </p>
                `;
            }
        }

        function submitCardPayment(pack) {
            // Récupération des données de la carte
            const cardNumber = document.getElementById('card_number')?.value.replace(/\s/g, '');
            const cardExpiry = document.getElementById('card_expiry')?.value;
            const cardCvv = document.getElementById('card_cvv')?.value;
            const cardHolder = document.getElementById('card_holder')?.value;

            // Validation basique côté client
            if (!cardNumber || cardNumber.length < 13) {
                alert('Veuillez saisir un numéro de carte valide.');
                return;
            }

            if (!cardExpiry || !cardExpiry.match(/^\d{2}\/\d{2}$/)) {
                alert('Veuillez saisir une date d\'expiration valide (MM/AA).');
                return;
            }

            if (!cardCvv || cardCvv.length < 3) {
                alert('Veuillez saisir un CVV valide.');
                return;
            }

            if (!cardHolder) {
                alert('Veuillez saisir le nom du titulaire de la carte.');
                return;
            }

            // Ici, vous allez intégrer votre système de paiement réel
            // Exemple avec Stripe, Paymeb, etc.
            
            // Pour l'instant, simulation d'envoi au serveur
            const formData = new FormData();
            formData.append('pack_key', pack);
            formData.append('card_number', cardNumber);
            formData.append('card_expiry', cardExpiry);
            formData.append('card_cvv', cardCvv);
            formData.append('card_holder', cardHolder);

            // Afficher un loader
            const btn = document.querySelector('.btn-primary');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement en cours...';
            btn.disabled = true;

            // Envoyer au serveur
            fetch('{{ route("payment.process") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    pack_key: pack,
                    card_number: cardNumber,
                    card_expiry: cardExpiry,
                    card_cvv: cardCvv,
                    card_holder: cardHolder
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Paiement réussi ! ' + data.message);
                    window.location.href = '{{ route("dashboard") }}';
                } else {
                    alert('❌ Erreur: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Une erreur est survenue. Veuillez réessayer.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        function resetPackSelection() {
            document.querySelectorAll('.pack-card').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.buy-section').forEach(s => s.classList.remove('active'));

            const firstCard = document.querySelector('.pack-card');
            const firstSection = document.querySelector('.buy-section');

            if (firstCard) firstCard.classList.add('active');
            if (firstSection) firstSection.classList.add('active');

            document.querySelector('.pack-grid')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Formatage automatique du numéro de carte
        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'card_number') {
                let value = e.target.value.replace(/\s/g, '');
                if (value.length > 16) value = value.slice(0, 16);
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                e.target.value = value;
            }
            
            if (e.target && e.target.id === 'card_expiry') {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                e.target.value = value;
            }
        });
    </script>

@endsection