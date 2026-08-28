<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1574275570334087');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1574275570334087&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
<div id="voucherCapture">
<div style="max-width:560px;margin:22px auto;font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f8;padding:14px;">
  <table width="100%" cellspacing="0" cellpadding="0" style="
    background:#ffffff;
    border-radius:16px;
    overflow:hidden;
    color:#1f2933;
    border-collapse:collapse;
    box-shadow:0 8px 24px rgba(0,0,0,0.08);
  ">
<div id="plantillaReciboHTML" style="max-width: 600px; margin: auto; font-family: 'Segoe UI', Arial, sans-serif; background-color: #ffffff; padding: 10px; margin-top: 25px;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 15px; overflow: hidden; color: #000000; border-collapse: collapse; border:1px solid #eee; box-shadow:0 8px 25px rgba(0,0,0,0.06);">
        
        <!-- HEADER -->
<tr>
    <td style="padding: 10px 15px; border-bottom: 2px solid #d500f9;">
        
        <table width="100%">
            <tr>

                <!-- TEXTO -->
                <td align="left">
                    <h1 style="color: #d500f9; margin: 0; font-size: 25px; letter-spacing: 3px; font-weight: 700;">
                        AP FENIX
                    </h1>
                    <p style="margin: 5px 0 0; font-size: 11px; color: #777; text-transform: uppercase; letter-spacing: 1.5px;">
                        El poder de ganar empieza aquí
                    </p>
                </td>

                <!-- BOTÓN -->
                <td align="right" style="vertical-align: middle;">
                    <a href="javascript:void(0)" onclick="shareVoucher(this)" title="Compartir"
                       style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; background:#f3e8ff; border:1px solid #d500f9; text-decoration:none;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d500f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.6" y1="13.5" x2="15.4" y2="17.5"></line>
                            <line x1="15.4" y1="6.5" x2="8.6" y2="10.5"></line>
                        </svg>
                    </a>
                </td>

            </tr>
        </table>

    </td>
</tr>
        
        <!-- SALUDO -->
        <tr>
            <td style="padding: 25px 25px 10px;" align="left">
                <p style="font-size: 14px; margin: 0; color: #000000;">
                    Hola,
                </p>
                <strong style="color: #d500f9; font-size: 15px; display: block; margin-top: 6px;">
                    {Nombre Cliente}
                </strong>
            </td>
        </tr>

        <!-- INFO -->
        <tr>
            <td style="padding: 0 25px 20px; text-align: left">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-size: 12px; border-top: 1px solid #eee; padding-top: 15px;">
                    
                    <tr>
                        <td style="padding: 6px 0; color:#333;"><strong>ID:</strong></td>
                        <td align="right" style="padding: 6px 0; color: #000; font-weight:100;">#{ID}</td>
                    </tr>

                    <tr>
                        <td style="padding: 6px 0; color:#333;"><strong>Fecha Compra:</strong></td>
                        <td align="right" style="padding: 6px 0; color: #000;">{Fecha}</td>
                    </tr>

                    <!-- <tr>
                        <td style="padding: 6px 0; color:#333;"><strong>Juega:</strong></td>
                        <td align="right" style="padding: 6px 0; color: #d500f9; font-weight: bold;">
                             17 de abril Por la de Medellin
                        </td>
                    </tr> -->

                    <tr>
                        <td style="padding: 6px 0; color:#333;"><strong>Cant. Números:</strong></td>
                        <td align="right" style="padding: 6px 0; color: #000; font-weight:500;">{Cantidad}</td>
                    </tr>

                    <!-- CODIGO -->
                    <tr>
                        <td colspan="2" style="padding: 12px 0 6px; color:#333;">
                            <strong>Código de Seguridad:</strong>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="padding: 12px; background-color: #f3f3f3; border-radius: 10px; color: #000; font-family: monospace; font-size: 15px; word-break: break-all; text-align: center; border:1px solid #e5e5e5; font-weight:bold; letter-spacing:1px;">
                            {Codigo}
                        </td>
                    </tr>

                </table>
            </td>
        </tr>

        <!-- NUMEROS -->
        <tr>
            <td align="center" style="padding: 0 20px 30px;">
                <div style="background-color: #fafafa; border: 2px dashed #d500f9; border-radius: 14px; padding: 22px;">
                    
                    <p style="margin: 0 0 15px; font-size: 12px; color: #d500f9; font-weight: bold; letter-spacing: 1.5px;">
                        TUS NÚMEROS
                    </p>

                    <div style="font-size:14px;">
                        {NumerosHTML}
                    </div>

                </div>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td align="center" style="padding: 25px 15px; background-color: #ffffff; border-top:1px solid #eee;">
                
                <p style="margin: 0; color: #777; font-size: 12px; text-transform: uppercase;">
                    Total Pagado
                </p>

                <h2 style="margin: 8px 0 25px; color: #d500f9; font-size: 20px; font-weight: 700;" id="totalCompra">
                    {Total}
                </h2>
                
                <!-- BOTONES -->
                <table border="0" cellspacing="0" cellpadding="0" align="">
                    <tr>
                        

                        <td style="padding: 0 10px;">
                            <a href="https://chat.whatsapp.com/KQg5y9T8vD98GqyYvjkDGJ?s=sw&p=i&mlu=4"
                               style="background: linear-gradient(135deg,#d500f9,#a100ff); color: #ffffff; padding: 10px 22px; border-radius: 999px; text-decoration: none; font-weight: bold; font-size: 12px; text-transform: uppercase; display: inline-block;">
                               Ir al grupo
                            </a>
                        </td>
                    </tr>
                </table>

                <p style="margin: 20px 0 0; font-size: 10px; color: #999;">
                    Hemos enviado una copia a tu correo electrónico.
                </p>

            </td>
        </tr>

    </table>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
    
    let el = document.getElementById('totalCompra');
    if (!el) return;
    
    let total = el.innerText
        .replace(/[^\d]/g, '') // limpia $, puntos, texto
        .trim();
    
    total = parseFloat(total);
    
    if (!isNaN(total)) {
        fbq('track', 'Purchase', {
            value: total,
            currency: 'COP'
        });
    }
    
    });
</script>