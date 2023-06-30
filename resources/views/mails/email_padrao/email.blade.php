<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>{{ config('app.name', 'MN Tecidos') }}</title>
        <style>*{line-height: 100%;}</style>
    </head>
    <body>
        <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%">
            <tr>
                <td align="center">
                    <table width="900" cellpadding="0" cellspacing="0" style="max-width: 900px;border: 1px solid #f3f3f3;">
                        <tr>
                            <td style="background: #003554; text-align: center; height: 60px;">
                                <a href="http://portal.mntecidos.com.br/" target="_blanck"><img src="http://portal.mntecidos.com.br/images/logotipo.png" border="0" alt="{{ config('app.name', 'MN Tecidos') }}" height="40"></a>
                            </td>
                        </tr>
                        <tr>
                            <td width="100%" cellpadding="0" cellspacing="0" style="padding: 20px 0;">
                                <table align="center" width="800" cellpadding="0" cellspacing="0" class="max-width: 800px;">
                                    <tr>
                                        <td class="content-cell">
                                            {!! $body !!}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td align="center"><font color="#000" size="3"><br />Este e-mail foi gerado automaticamente pelo sistema por favor não responda!</font></td>
            </tr>
        </table>
    </body>
</html>