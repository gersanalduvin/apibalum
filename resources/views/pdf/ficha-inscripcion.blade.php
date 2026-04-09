<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Inscripción - {{ $alumno->nombres ?? 'Alumno' }} {{ $alumno->apellidos }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
        }


        .title {
            display: inline-block;
            vertical-align: middle;
        }
        .title h1 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }
        .title h2 {
            margin: 0;
            font-size: 12px;
            font-weight: normal;
        }
        .section {
            margin-bottom: 3px;
            width: 100%;
        }
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
            width: 100%;
        }
        /* ESTILOS PARA TABLAS - Compatible con wkhtmltopdf */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            table-layout: fixed;
        }

        .info-table td {
            padding: 0;
            vertical-align: top;
            border-bottom: 1px solid #dfdddd;
        }

        /* Estilo para celdas que contienen label y value juntos */
        .info-table .field-cell {
            width: 33.33%; /* Columnas iguales para 3 columnas */
            vertical-align: top;
            padding: 1px 1px;
            line-height: 1.3;
            margin-bottom: 4px;
        }

        .info-table .field-cell .field-label {
            font-weight: bold;
            display: inline-block;
            vertical-align: top;
        }

        .info-table .field-cell .field-value {
            padding-bottom: 1px;
            padding-top: 1px;
            padding-left: 5px;
            display: inline-block;
            width: calc(100% - 140px);
            vertical-align: top;
        }

        /* Tabla para campos de 2 columnas */
        .info-table-2col .field-cell {
            width: 50%; /* Columnas iguales para 2 columnas */
        }

        /* Tabla para campos de 3 columnas */
        .info-table-3col .field-cell {
            width: 33.33%; /* Columnas iguales para 3 columnas */
        }

        /* Tabla para campos de ancho completo */
        .info-table-full .field-cell {
            width: 100%; /* Una sola columna de ancho completo */
        }

        /* Estilos para campos individuales (compatibilidad) */
        .field {
            margin-bottom: 8px;
            width: 100%;
        }

        .field-label {
            font-weight: bold;
            display: inline-block;
        }

        .field-value {
            padding-bottom: 1px;
            padding-left: 5px;
            min-width: 100px;
            display: inline-block;
        }
        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 5px;
            text-align: center;
            line-height: 10px;
        }

        .signature-section {
            margin-top: 20px;
            width: 100%;
            display: table;
        }
        .signature-box {
            text-align: center;
            width: 48%;
            display: table-cell;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 30px;
            width: 100%;
        }
        .page-break {
            page-break-before: always;
        }
        .full-width {
            width: 100%;
        }
        .half-width {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
    </style>
</head>
<body>


        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Número ROC:</div>
                    <div class="field-value">{{ $alumno->numero_recibo }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Fecha de inscripción:</div>
                    <div class="field-value">{{ $alumno->fecha_inscripcion }}</div>
                </td>
            </tr>
            <tr>
                <td class="field-cell">
                    <div class="field-label">Nivel Académico:</div>
                    <div class="field-value">{{ $alumno->grado }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Turno:</div>
                    <div class="field-value">{{ $alumno->turno }}</div>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Tipo de ingreso:</div>
                    <div class="field-value">{{ $alumno->tipo_ingreso }}</div>
                </td>
            </tr>
        </table>


        <div class="section-title">DATOS GENERALES.</div>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Nombre del alumno(a):</div>
                    <div class="field-value">{{ $alumno->nombres }} {{ $alumno->apellidos }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-3col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Fecha de nacimiento:</div>
                    <div class="field-value">{{ $alumno->fecha_nacimiento_espanol }}</div>
                </td>

                <td class="field-cell">
                    <div class="field-label">Edad actual:</div>
                    <div class="field-value">{{ $alumno->edad }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Sexo:</div>
                    <div class="field-value">{{ $alumno->sexo_text }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-2col">
            <tr>
                  <td class="field-cell">
                    <div class="field-label">Lugar de nacimiento:</div>
                    <div class="field-value">{{ $alumno->lugar_nacimiento }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Código MINED:</div>
                    <div class="field-value">{{ $alumno->codigo_mined }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Código Único:</div>
                    <div class="field-value">{{ $alumno->codigo_unico }}</div>
                </td>
            </tr>
        </table>


        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Nombre de la madre:</div>
                    <div class="field-value">{{ $alumno->nombre_madre }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-3col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Edad:</div>
                    <div class="field-value">{{ $alumno->edad_madre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Cédula:</div>
                    <div class="field-value">{{ $alumno->cedula_madre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Religión:</div>
                    <div class="field-value">{{ $alumno->religion_madre }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Estado civil:</div>
                    <div class="field-value">{{ $alumno->estado_civil_madre_text }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Teléfono:</div>
                    <div class="field-value">{{ $alumno->telefono_madre }}</div>
                </td>
                  <td class="field-cell">
                    <div class="field-label">Claro:</div>
                    <div class="field-value">{{ $alumno->telefono_claro_madre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Tigo:</div>
                    <div class="field-value">{{ $alumno->telefono_tigo_madre }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Dirección del domicilio:</div>
                    <div class="field-value">{{ $alumno->direccion_madre }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Barrio:</div>
                    <div class="field-value">{{ $alumno->barrio_madre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Ocupación:</div>
                    <div class="field-value">{{ $alumno->ocupacion_madre }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-full">
            <tr>
                 <td class="field-cell">
                    <div class="field-label">Lugar de trabajo:</div>
                    <div class="field-value">{{ $alumno->lugar_trabajo_madre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Teléfono trabajo:</div>
                    <div class="field-value">{{ $alumno->telefono_trabajo_madre }}</div>
                </td>
            </tr>
        </table>
        <!-- INFORMACIÓN DEL PADRE -->

        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Nombre del padre:</div>
                    <div class="field-value">{{ $alumno->nombre_padre }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-3col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Edad:</div>
                    <div class="field-value">{{ $alumno->edad_padre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Cédula:</div>
                    <div class="field-value">{{ $alumno->cedula_padre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Religión:</div>
                    <div class="field-value">{{ $alumno->religion_padre }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-3col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Estado Civil:</div>
                    <div class="field-value">{{ $alumno->estado_civil_padre_text }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Teléfono:</div>
                    <div class="field-value">{{ $alumno->telefono_padre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Claro:</div>
                    <div class="field-value">{{ $alumno->telefono_claro_padre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Tigo:</div>
                    <div class="field-value">{{ $alumno->telefono_tigo_padre }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Dirección del domicilio:</div>
                    <div class="field-value">{{ $alumno->direccion_padre }}</div>
                </td>

            </tr>
        </table>



        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Barrio:</div>
                    <div class="field-value">{{ $alumno->barrio_padre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Ocupación:</div>
                    <div class="field-value">{{ $alumno->ocupacion_padre }}</div>
                </td>
            </tr>
            <tr>
                <td class="field-cell">
                    <div class="field-label">Lugar de trabajo:</div>
                    <div class="field-value">{{ $alumno->lugar_trabajo_padre }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Teléfono trabajo:</div>
                    <div class="field-value">{{ $alumno->telefono_trabajo_padre }}</div>
                </td>
            </tr>
        </table>

        <!-- INFORMACIÓN DEL RESPONSABLE -->
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Nombre del responsable del niño o niña en caso de que los padres estén fuera del país:</div>
                    <div class="field-value">{{ $alumno->nombre_responsable }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Cédula:</div>
                    <div class="field-value">{{ $alumno->cedula_responsable }}</div>
                </td>
                <td class="field-cell">
                    <div class="field-label">Teléfono:</div>
                    <div class="field-value">{{ $alumno->telefono_responsable }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <div class="field-label">Dirección:</div>
                    <div class="field-value">{{ $alumno->direccion_responsable }} - fotocopia del poder.</div>
                </td>
            </tr>
        </table>

        <div class="section-title">DATOS FAMILIARES.</div>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Cantidad de hijos:</span>
                    <span class="field-value">{{ $alumno->cantidad_hijos }}</span>
                    <span class="field-label">Lugar del niño en relación a sus hermanos:</span>
                    <span class="field-value">{{ $alumno->lugar_en_familia }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Personas que habitan en su hogar:</span>
                    <span class="field-value">{{ $alumno->personas_hogar }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Personas que están a cargo del alumno(a):</span>
                    <span class="field-value">{{ $alumno->encargado_alumno }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">En Caso de Emergencia llamar a:</span>
                    <span class="field-value">{{ $alumno->contacto_emergencia }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Teléfono de emergencia:</span>
                    <span class="field-value">{{ $alumno->telefono_emergencia }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Métodos disciplinarios que emplean en casa con su niño o niña:</span>
                    <span class="field-value">{{ $alumno->metodos_disciplina }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Pasatiempos y actividades familiares:</span>
                    <span class="field-value">{{ $alumno->pasatiempos_familiares }}</span>
                </td>
            </tr>
        </table>
        <div class="section-title">DATOS RELATIVOS AL ALUMNO.</div>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Descripción de la personalidad del alumno(a):</span>
                    <span class="field-value">{{ $alumno->personalidad }}</span>
                </td>
            </tr>
        </table>

         <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Al nacer fue parto:</span>
                    <span class="field-value">{{ $alumno->parto_text }}</span>
                </td>
                <td class="field-cell">
                    <div class="field-label">Tuvo sufrimiento fetal:</div>
                    <div class="field-value">{{ $alumno->sufrimiento_fetal ? 'Sí' : 'No' }}</div>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">A los cuantos meses: empezo a gatear</span>
                    <span class="field-value">{{ $alumno->edad_gateo }}</span>
                     <div class="field-label">empezó a caminar:</div>
                    <div class="field-value">{{ $alumno->edad_caminar }}</div>
                     <div class="field-label">y a hablar:</div>
                    <div class="field-value">{{ $alumno->edad_hablar }}</div>
                </td>

            </tr>
        </table>
          <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Habilidades que tiene el niño o niña:</span>
                    <span class="field-value">{{ $alumno->habilidades }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Pasatiempos:</span>
                    <span class="field-value">{{ $alumno->pasatiempos }}</span>
                </td>
                   <td class="field-cell">
                    <span class="field-label">Preocupaciones:</span>
                    <span class="field-value">{{ $alumno->preocupaciones }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Juegos preferidos:</span>
                    <span class="field-value">{{ $alumno->juegos_preferidos }}</span>
                </td>
            </tr>
        </table>

        <div class="section-title">ÁREA SOCIAL.</div>
         <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Se relaciona con familiares:</span>
                    <span class="field-value">{{ $alumno->se_relaciona_familiares ? 'Sí' : 'No' }}</span>
                </td>
                <td class="field-cell">
                    <span class="field-label">Establece relación con coetáneos:</span>
                    <span class="field-value">{{ $alumno->establece_relacion_coetaneos ? 'Sí' : 'No' }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Evita contacto con personas:</span>
                    <span class="field-value">{{ $alumno->evita_contacto_personas ? 'Sí' : 'No' }}</span>
                </td>
                   <td class="field-cell">
                    <span class="field-label">Especifique:</span>
                    <span class="field-value">{{ $alumno->especifique_evita_personas }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Evita lugares o situaciones:</span>
                    <span class="field-value">{{ $alumno->evita_lugares_situaciones ? 'Sí' : 'No' }}</span>
                </td>
                   <td class="field-cell">
                    <span class="field-label">Especifique:</span>
                    <span class="field-value">{{ $alumno->especifique_evita_lugares }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Respeta figuras de autoridad:</span>
                    <span class="field-value">{{ $alumno->respeta_figuras_autoridad ? 'Sí' : 'No' }}</span>
            </tr>
        </table>

        <div class="section-title">ÁREA COMUNICATIVA.</div>
        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Atiende cuando se le llama por su nombre:</span>
                    <span class="field-value">{{ $alumno->atiende_cuando_llaman ? 'Sí' : 'No' }}</span>
                 </td>
                    <td class="field-cell">
                     <span class="field-label">Es capaz de comunicarse:</span>
                     <span class="field-value">{{ $alumno->es_capaz_comunicarse ? 'Sí' : 'No' }}</span>
                 </td>
             </tr>
         </table>
          <table class="info-table info-table-full">
             <tr>
                 <td class="field-cell">
                     <span class="field-label">Por medio de: Palabras</span>
                     <span class="field-value">{{ $alumno->comunica_palabras ? 'Sí' : 'No' }}</span>
                      <span class="field-label">Señas: </span>
                     <span class="field-value">{{ $alumno->comunica_señas ? 'Sí' : 'No' }}</span>
                      <span class="field-label">Llanto: </span>
                     <span class="field-value">{{ $alumno->comunica_llanto ? 'Sí' : 'No' }}</span>
                 </td>
             </tr>
         </table>
         <table class="info-table info-table-2col">
             <tr>
                 <td class="field-cell">
                     <span class="field-label">Dificultad para expresarse:</span>
                     <span class="field-value">{{ $alumno->dificultad_expresarse ? 'Sí' : 'No' }}</span>
                 </td>
                  <td class="field-cell">
                     <span class="field-label">Especifique:</span>
                     <span class="field-value">{{ $alumno->especifique_dificultad_expresarse }}</span>
                 </td>
             </tr>
         </table>
           <table class="info-table info-table-2col">
             <tr>
                 <td class="field-cell">
                     <span class="field-label">Dificultad para comprender:</span>
                     <span class="field-value">{{ $alumno->dificultad_comprender ? 'Sí' : 'No' }}</span>
                 </td>
                  <td class="field-cell">
                     <span class="field-label">Especifique:</span>
                     <span class="field-value">{{ $alumno->especifique_dificultad_comprender }}</span>
                 </td>
             </tr>
         </table>
         <table class="info-table info-table-full">
             <tr>
                 <td class="field-cell">
                     <span class="field-label">Atiende orientaciones:</span>
                     <span class="field-value">{{ $alumno->atiende_orientaciones ? 'Sí' : 'No' }}</span>
                 </td>
             </tr>
         </table>
         <div class="section-title">ÁREA PSICOLÓGICA.</div>
          <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">El estado de ánimo del alumno(a) generalmente es:</span>
                    <span class="field-value">{{ $alumno->estado_animo_general_text }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Fobias:</span>
                    <span class="field-value">{{ $alumno->tiene_fobias ? 'Sí' : 'No' }}</span>
                </td>
                <td class="field-cell">
                    <span class="field-label">Especifique el generador de la fobia:</span>
                    <span class="field-value">{{ $alumno->generador_fobia }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Agresividad:</span>
                    <span class="field-value">{{ $alumno->tiene_agresividad ? 'Sí' : 'No' }}</span>
                </td>
                <td class="field-cell">
                    <span class="field-label">En caso de existir es:</span>
                    <span class="field-value">{{ $alumno->tipo_agresividad_text }}</span>
                </td>
            </tr>
        </table>
        <div class="section-title">ÁREA MÉDICA.</div>
          <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Patologías que padece el alumno:</span>
                    <span class="field-value">{{ $alumno->patologias_detalle }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Consume fármacos:</span>
                    <span class="field-value">{{ $alumno->consume_farmacos ? 'Sí' : 'No' }}</span>
                </td>
                 <td class="field-cell">
                    <span class="field-label">Especifique cuales:</span>
                    <span class="field-value">{{ $alumno->farmacos_detalle }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-2col">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Alergias:</span>
                    <span class="field-value">{{ $alumno->tiene_alergias ? 'Sí' : 'No' }}</span>
                </td>
                 <td class="field-cell">
                    <span class="field-label">Causas de la alergia:</span>
                    <span class="field-value">{{ $alumno->causas_alergia }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Alteraciones del patrón de sueño:</span>
                    <span class="field-value">{{ $alumno->alteraciones_patron_sueño ? 'Sí' : 'No' }}</span>
                    <span class="field-label">Se duerme: Temprano</span>
                     <span class="field-value">{{ $alumno->se_duerme_temprano ? 'Sí' : 'No' }}</span>
                      <span class="field-label">Tarde:</span>
                     <span class="field-value">{{ $alumno->se_duerme_tarde ? 'Sí' : 'No' }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-3col">
            <tr>
                <td class="field-cell">
                     <span class="field-label">Apnea del sueño:</span>
                     <span class="field-value">{{ $alumno->apnea_sueño ? 'Sí' : 'No' }}</span>
                 </td>
                 <td class="field-cell">
                     <span class="field-label">Pesadillas:</span>
                     <span class="field-value">{{ $alumno->pesadillas ? 'Sí' : 'No' }}</span>
                 </td>
                  <td class="field-cell">
                     <span class="field-label">Enuresis secundaria:</span>
                     <span class="field-value">{{ $alumno->enuresis_secundaria ? 'Sí' : 'No' }}</span>
                 </td>
            </tr>
        </table>
        <table class="info-table info-table-3col">
            <tr>
                <td class="field-cell">
                     <span class="field-label">Alteraciones del apetito:</span>
                     <span class="field-value">{{ $alumno->alteraciones_apetito_detalle ? 'Sí' : 'No' }}</span>
                 </td>
                 <td class="field-cell">
                     <span class="field-label">Adversión a ciertos alimientos:</span>
                     <span class="field-value">{{ $alumno->aversion_alimentos }}</span>
                 </td>
                  <td class="field-cell">
                     <span class="field-label">Reflujo:</span>
                     <span class="field-value">{{ $alumno->reflujo ? 'Sí' : 'No' }}</span>
                 </td>
            </tr>
        </table>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Alimentos favoritos:</span>
                    <span class="field-value">{{ $alumno->alimentos_favoritos }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Alteraciones de los organos de los sentidos:</span>
                    <span class="field-label">Visión:</span>
                    <span class="field-value">{{ $alumno->alteracion_vision ? 'Sí' : 'No' }}</span>
                     <span class="field-label">Audición:</span>
                    <span class="field-value">{{ $alumno->alteracion_audicion ? 'Sí' : 'No' }}</span>
                     <span class="field-label">Tacto:</span>
                    <span class="field-value">{{ $alumno->alteracion_tacto ? 'Sí' : 'No' }}</span>
                </td>
            </tr>
        </table>
         <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Especifique las alteraciones de los sentidos:</span>
                    <span class="field-value">{{ $alumno->especifique_alteraciones_sentidos }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Alteraciones óseas:</span>
                    <span class="field-value">{{ $alumno->alteraciones_oseas ? 'Sí' : 'No' }}</span>
                     <span class="field-label">Alteraciones musculares:</span>
                    <span class="field-value">{{ $alumno->alteraciones_musculares ? 'Sí' : 'No' }}</span>
                      <span class="field-label">Pie plano:</span>
                    <span class="field-value">{{ $alumno->pie_plano ? 'Sí' : 'No' }}</span>
                </td>
            </tr>
        </table>
         <div class="field">
            <ul>
                <li>En el caso de que su hijo(a) sea un niño con capacidades diferentes, traer el diagnóstico médico actualizado: <span style="font-weight: bold;">{{ $alumno->diagnostico_medico }}</span></li>
                <li>Es referido de escuela especial: <span style="font-weight: bold;">{{ $alumno->referido_escuela_especial ? 'Sí' : 'No' }}</span> (en caso de que sea un niño especial el padre de familia o responsable deberá pagar una matrícula personal).</li>
                <li>Al momento de matricular presenta el diagnóstico en el cual se especifica el problema que el niño tiene: <span style="font-weight: bold;">{{ $alumno->presenta_diagnostico_matricula ? 'Sí' : 'No' }}</span></li>
                <li>Todo niño con capacidades diferentes, requiere su asistente personal la cual el padre de familia o responsable deberá pagar, de enero a noviembre y aguinaldo.</li>
            </ul>
        </div>

        <div style="margin-top: 20px; font-weight: bold;">
            Llenar estos datos en caso de que su niño(a) sea el retiro del centro (llenado por la maestra del centro):
        </div>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Fecha de retiro:</span>
                    <span class="field-value">{{ $alumno->fecha_retiro_espanol }}</span>
                     <span class="field-label">Es notificado por sus Padres:</span>
                    <span class="field-value">{{ $alumno->retiro_notificado ? 'Sí' : 'No' }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Motivo del retiro:</span>
                    <span class="field-value">{{ $alumno->motivo_retiro }}</span>
                </td>
            </tr>
        </table>
          <table class="info-table info-table-full">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Observaciones (Comentario de las cosas que usted cree es importante conozca su nueva Maestra):</span>
                    <span class="field-value">{{ $alumno->observaciones }}</span>
                </td>
            </tr>
        </table>

        <div style="margin-top: 15px;">
            <div style="font-weight: bold; font-style:italic; text-align:justify">El Centro no realizará ningún reembolso monetario ni por matrícula, ni mensualidad, ni por artículos escolares que usted entrega en el Centro. En caso de que usted retire a su hijo deberá notificarlo a Secretaría o a la Dirección, el centro no regresará los materiales de consumo escolar, solo los libros, cartuchera, delantal y su carpeta con los trabajos realizados, cuando haya pagado la mensualidad del último mes que el niño asistió a clases.</div>
            <div style="font-style:justify;">Es prohibido que los alumnos traigan celulares, juegos electrónicos y me comprometo a regirme por el reglamento escolar.</div>
            <div style="font-style:justify;">“Todos los datos suministrados en la ficha anterior son completamente verídicos y confiables, en caso de distorsión y/u omisión de información solicitada se le atribuye al padre o tutor. Como responsable del alumno(a) asumo el alcance de la misma y me responsabilizo a contribuir a su debida actualización.</div>
            <div style="font-align: justify; font-weight:bold; font-style:italic;">Al momento de realizar esta matrícula para el año escolar {{ $alumno->periodo_lectivo}}, nosotros como padres de familia aceptamos todas las medidas que el centro tome ante diferentes eventualidades que se puedan presentar, ejemplo: como el problema de la pandemia mundial y otros. Me comprometo a apoyar y participar directamente en las actividades que el centro promueve y organiza a lo largo del año escolar”. Asumo la responsabilidad de realizar en el tiempo establecido por el centro el pago de los aranceles escolares. El Centro realiza un Recital Poético, una Gala Navideña y un Proyecto Anual con su revista (es exigido pagar los tickets y la revista).</div>
        </div>
         <table class="info-table info-table-full" style="margin-top: 20px;">
            <tr>
                <td class="field-cell">
                    <span class="field-label">Nombre del padre, responsable o persona que matricula:</span>
                    <span class="field-value">{{ $alumno->nombre_persona_firma }}</span>
                </td>
            </tr>
            <tr>
                <td class="field-cell">
                    <span class="field-label">No de cédula::</span>
                    <span class="field-value">{{ $alumno->cedula_firma }}</span>
                </td>
            </tr>
        </table>
        <table style="margin-top: 100px;width: 100%;">
            <tr>
                <td class="field-cell" style="width: 50%; padding-right: 20px;">
                     <div class="signature-box">
                        <div class="signature-line"></div>
                        <div>Firma del Director del Centro:</div>
                    </div>
                </td>
                <td class="field-cell" style="width: 50%; padding-left: 20px;">
                       <div class="signature-box" style="margin-left: 20px;">
                            <div class="signature-line"></div>
                            <div>Firma del Padre o Responsable:</div>
                        </div>
                </td>
            </tr>
        </table>
</body>
</html>
