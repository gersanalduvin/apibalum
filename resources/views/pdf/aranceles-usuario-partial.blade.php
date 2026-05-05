<div class="student-info">
    <strong>ALUMNO:</strong> {{ $user->primer_nombre }} {{ $user->segundo_nombre }} {{ $user->primer_apellido }} {{ $user->segundo_apellido }}<br>
    <strong>GRADO:</strong> {{ $grado->nombre ?? 'N/A' }} @if($grupo) | <strong>GRUPO:</strong> {{ $grupo->nombre }} @endif
</div>

<table>
    <thead>
        <tr>
            <th style="width: 50%;">RUBRO</th>
            <th style="width: 25%;" class="text-center">ESTADO</th>
            <th style="width: 25%;" class="text-right">SALDO ACTUAL</th>
        </tr>
    </thead>
    <tbody>
        @php
        $totalSaldo = 0;
        @endphp
        @foreach($aranceles as $arancel)
        @php
        $totalSaldo += (float)$arancel->saldo_actual;
        @endphp
        <tr>
            <td>
                <strong>{{ $arancel->rubro->nombre ?? 'S/N' }}</strong>
                @if($arancel->arancel)
                <br><small>{{ $arancel->arancel->nombre }}</small>
                @endif
            </td>
            <td class="text-center">
                {{ strtoupper($arancel->estado) }}
            </td>
            <td class="text-right"><strong>C$ {{ number_format($arancel->saldo_actual, 2) }}</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals-table">
    <tr class="grand-total">
        <td class="text-right">SALDO PENDIENTE:</td>
        <td class="text-right">C$ {{ number_format($totalSaldo, 2) }}</td>
    </tr>
</table>

<div style="margin-top: 30px; padding: 15px; border: 2px solid #d32f2f; background-color: #fff4f4; color: #d32f2f; border-radius: 8px; text-align: center; font-size: 13px; font-weight: bold; clear: both;">
    ¡IMPORTANTE!: Después del día 10 de cada mes se aplica un recargo por mora de C$ 295.00 Córdobas.
</div>
