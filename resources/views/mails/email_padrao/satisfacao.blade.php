<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>MN Tecidos</title>
        <style>*{line-height: 100%;font-family: Arial, Helvetica, sans-serif;}</style>
    </head>
    <body style="margin: 0;padding: 0;">
        <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%">
            <tr>
                <td align="center">
                    <table width="900" cellpadding="0" cellspacing="0" style="max-width: 900px;"">
                        <tr>
                            <td bgcolor="#003554" style="text-align: center; width: 900px; height: 150px;" height="150" width="900">
								<table align="center" height="150" width="800" cellpadding="0" cellspacing="0" class="max-width: 800px;">
									<tr>
										<td><h1 style="color: #FFFFFF;float: left;">Queremos <b style="color:#FF8C00">ESCUTAR VOCÊ!</b></h1></td>
										<td><a href="{{ route('home') }}" target="_blanck" style="float: rigth;"><img src="{{ URL::asset('/images/logotipo.png') }}" border="0" alt="MN Tecidos" height="60"></a></td>
									</tr>
								</table>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" width="100%" cellpadding="0" cellspacing="0" style="padding: 20px 0;">
                                <table width="800" cellpadding="0" cellspacing="0" class="max-width: 800px;">
                                    <tr>
                                        <td class="content-cell">
                                            {!! $body !!}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="100%" cellpadding="0" cellspacing="0" style="padding: 20px 0;text-align: center;">
                                <img src="{{ URL::asset('/images/logomn.jpg') }}" border="0" alt="MN Tecidos" height="70">
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor="#003554" style="text-align: center; height: 150px;">
								<table align="center" height="150" width="800" cellpadding="0" cellspacing="0" class="max-width: 800px;text-align: center;">
									<tr>
										<td style="padding: 20px 0;text-align: center;">
											<a href="http://mntecidos.com.br/" target="_blanck"><img src="{{ URL::asset('/images/icons/emails/icon_site.png') }}" border="0" alt="MN Tecidos" height="60"></a>
											<a href="https://www.linkedin.com/company/textil-mn-comercio-de-tecidos-e-confeccoes/" target="_blanck"><img src="{{ URL::asset('/images/icons/emails/icon_linkedin.png') }}" border="0" alt="LinkedIn" height="60"></a>
											<a href="https://www.instagram.com/mntecidos/?hl=pt-br" target="_blanck"><img src="{{ URL::asset('/images/icons/emails/icon_instagram.png') }}" border="0" alt="Instagram" height="60"></a>
										</td>
									</tr>
								</table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
