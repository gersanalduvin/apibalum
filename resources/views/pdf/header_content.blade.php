<style>
    .header-table {
        width: 100%;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
        margin-bottom: 5px;
        border-collapse: collapse;
    }

    .header-logo-td {
        width: 70px;
        text-align: left;
        vertical-align: middle;
    }

    .header-logo-td img {
        width: 80px !important;
        height: auto;
        max-height: 80px;
    }

    .header-content-td {
        text-align: center;
        vertical-align: middle;
    }

    .header-right-td {
        text-align: right;
        vertical-align: middle;
        width: 160px;
    }

    .header-title {
        font-weight: bold;
        font-size: 28px;
        color: #000;
        margin-bottom: 2px;
    }

    .header-subtitle {
        font-weight: bold;
        font-size: 20px;
        color: #000;
        margin-top: 1px;
    }

    .header-data-text {
        font-weight: bold;
        font-size: 20px;
        color: #000;
    }
</style>
<table class="header-table">
    <tr>
        <td class="header-logo-td">
            @php
            $perfilKey = $perfil ?? config('institucion.default');
            $perfilConfig = config("institucion.{$perfilKey}");
            $logoRelPath = $perfilConfig['logo'] ?? 'logopp.jpg';
            $instNombre = $perfilConfig['nombre'] ?? config('app.nombre_institucion');

            $logoPath = public_path($logoRelPath);
            $logoDataUri = null;
            if (file_exists($logoPath)) {
                try {
                $logoDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
                } catch (\Throwable $e) { $logoDataUri = null; }
            }
            @endphp
            @if($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="Logo">
            @endif
        </td>
        <td class="header-content-td">
            <div class="header-title">{{ $instNombre }}</div>
            <div class="header-subtitle">{{ $titulo ?? 'DOCUMENTO OFICIAL' }}</div>
            <div style="font-size: 12px; font-weight: normal; margin-top: 4px;">
                Repto, Posada de Sol - Telf:. 2311-2813 - Cel.: 7603-1004 / 8190-1227
            </div>
        </td>
        <td class="header-right-td">
            @if(isset($subtitulo1)) <div class="header-data-text">{{ $subtitulo1 }}</div> @endif
            @if(isset($subtitulo2)) <div class="header-data-text">{{ $subtitulo2 }}</div> @endif
        </td>
    </tr>
</table>