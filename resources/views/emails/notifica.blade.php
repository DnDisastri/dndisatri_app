{{-- Stili in linea, tabelle e colori scritti a mano: i client di posta ignorano fogli di stile e variabili. --}}
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titolo }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border:1px solid #d9d9d9; border-radius:12px; overflow:hidden;">

                    <tr>
                        <td style="background-color:#2c3e6e; padding:20px 24px;">
                            <span style="color:#f5f5f5; font-size:18px; font-weight:bold; letter-spacing:0.02em;">D&amp;Disastri</span>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 24px 8px;">
                            <p style="margin:0 0 4px; color:#666666; font-size:13px;">Ciao {{ $destinatario->name }},</p>
                            <h1 style="margin:0 0 12px; color:#1a1a1a; font-size:21px; line-height:1.3;">{{ $titolo }}</h1>
                            <p style="margin:0; color:#1a1a1a; font-size:15px; line-height:1.6;">{!! nl2br(e($corpo)) !!}</p>
                        </td>
                    </tr>

                    @if ($indirizzo)
                        <tr>
                            <td style="padding:24px;">
                                <a href="{{ $indirizzo }}"
                                   style="display:inline-block; background-color:#d4423e; color:#ffffff; font-size:15px;
                                          font-weight:bold; text-decoration:none; padding:12px 24px; border-radius:999px;">
                                    Vai a vedere
                                </a>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="border-top:1px solid #d9d9d9; padding:16px 24px; color:#666666; font-size:12px; line-height:1.6;">
                            Ricevi questa email perché nel tuo profilo è attiva la categoria
                            «{{ $categoria->label() }}».
                            Puoi disattivarla quando vuoi <a href="{{ route('profile.edit') }}" style="color:#2c3e6e;">dal tuo profilo</a>.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
