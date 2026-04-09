<div class="portada">
    <table style="width: 100%; border: none; table-layout: fixed; border-collapse: collapse;">
        <tr>
            <!-- COLUMNA IZQUIERDA: PADRE MIO -->
            <td style="width: 50%; max-width: 50%; min-width: 50%; vertical-align: top; padding-right: 20px; border: none; text-align: center;">
                <div class="padre-mio" style="margin-top: 100px;">
                    <h3 style="margin-bottom: 20px; font-size: 14px;">PADRE MIO</h3>
                    <p style="padding:0px; margin:0px">Padre dame el día de hoy fe para seguir adelante;</p>
                    <p style="padding:0px; margin:0px">Dame grandeza de espíritu para perdonar;</p>
                    <p style="padding:0px; margin:0px">Dame paciencia para comprender y esperar;</p>

                    <p style="margin-top: 15px;">Dame voluntad para no caer;</p>
                    <p style="padding:0px; margin:0px">Dame fuerza para levantarme sin caído estoy;</p>
                    <p style="padding:0px; margin:0px">Dame amor para dar;</p>

                    <p style="margin-top: 15px;">Dame lo que necesito y no lo quiero;</p>
                    <p style="padding:0px; margin:0px">Dame elocuencia para decir lo que debo decir;</p>
                    <p style="padding:0px; margin:0px">Haz que yo sea el mejor ejemplo de mis amigos;</p>
                    <p style="padding:0px; margin:0px">Haz que yo sea el mejor amigo de mis amigos;</p>

                    <p style="margin-top: 15px;">Hazme fuerte para recibir los golpes de la vida;</p>
                    <p style="padding:0px; margin:0px">Déjame saber que es lo que tú quieres de mí;</p>
                    <p style="padding:0px; margin:0px">Déjame que tu paz para que la comparta con quien no la tenga;</p>

                    <p style="margin-top: 15px;">Anda conmigo y déjame saber que es así.</p>
                </div>
            </td>

            <!-- COLUMNA DERECHA: LOGO, ENCABEZADO, DATOS -->
            <td style="width: 50%; max-width: 50%; min-width: 50%; vertical-align: top; border: none;">
                <div style="text-align: center; font-size: 10pt; padding-left:10px;padding-right:10px;">
                    <h4 style="margin: 0;">REPÚBLICA DE NICARAGUA</h4>
                    <h4 style="margin: 2px 0;">MINISTERIO DE EDUCACIÓN</h4>
                    <h4 style="margin: 2px 0;">BOLETÍN ESCOLAR</h4>
                    <h4 style="margin: 2px 0;">EDUCACIÓN SECUNDARIA</h4>

                    <div style="margin: 15px 0;">
                        <h4 style="margin: 0;">{{ $nombreInstitucion ?? config('app.nombre_institucion', 'COLEGIO PRIMEROS PASOS') }}</h4>
                        <h4 style="margin: 2px 0;">{{ config('app.subtitulo_institucion', '') }}</h4>
                    </div>

                    <div class="logo" style="margin: 20px 0;">
                        @if(file_exists(public_path('logopp.png')))
                        <img src="{{ public_path('logopp.png') }}" alt="Logo" style="width: 80px;">
                        @else
                        <div style="width: 80px; height: 80px; border: 1px dashed #ccc; margin: 0 auto; line-height: 80px; font-size: 10px; color: #999;">LOGO</div>
                        @endif
                    </div>
                </div>

                <div class="estudiante-info" style="text-align: left; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="display:inline-block; width: 48%;"><strong>Código del estudiante:</strong> <u>{{ !empty($estudiante->codigo_unico) ? $estudiante->codigo_unico : '___________________' }}</u></span>
                        <span style="display:inline-block; width: 48%; text-align: right;"><strong>Año Lectivo:</strong> <u>{{ $periodo_lectivo->anio ?? $periodo_lectivo->nombre ?? date('Y') }}</u></span>
                    </div>

                    <p style="margin: 5px 0;"><strong>Nombre del Estudiante:</strong> <u style="text-decoration: underline;">{{ $estudiante->primer_nombre }} {{ $estudiante->segundo_nombre ?? '' }} {{ $estudiante->primer_apellido }} {{ $estudiante->segundo_apellido ?? '' }}</u></p>
                    <p style="margin: 5px 0;"><strong>Nombre de la docente:</strong> <u>{{ $grupo->docenteGuia->primer_nombre ?? '' }} {{ $grupo->docenteGuia->primer_apellido ?? '' }}</u></p>
                    <p style="margin: 5px 0;"><strong>Nombre del Director(a):</strong> ___________________________________</p>

                    <p style="margin: 5px 0;">
                        <strong>Grado:</strong> <u>{{ $grupo->grado->nombre ?? '___' }}</u> &nbsp;
                        <strong>Turno:</strong> <u>{{ $grupo->turno->nombre ?? '___' }}</u> &nbsp;
                        <strong>Modalidad:</strong> Regular
                    </p>
                    <p style="margin: 5px 0;"><strong>Departamento:</strong> Granada <strong>Municipio:</strong> Diriomo</p>
                </div>

                <div class="deberes" style="margin-top: 30px;">
                    <h5 style="text-align: center; margin-bottom: 12px;font-size: 10px;">Deberes de los padres</h5>
                    <ol start="1">
                        <li>Enviar a sus hijos con puntualidad, el debido cumplimiento de las tareas escolares y proporcionar los útiles necesarios para el aprendizaje.</li>
                        <li>Visitar frecuentemente la Escuela para informarse de la conducta, asistencia y aprovechamiento de los hijos.</li>
                        <li>Firmar y observar bimestralmente las calificaciones y recomendaciones del boletín.</li>
                        <li>Asistir a reunión de Padres de familia y actividades escolares en las que se requiera la asistencia de los padres.</li>
                    </ol>
                </div>
                <div style="text-align: center; font-style: italic; margin-top: 40px; font-size: 12pt; font-weight: bold;">
                    "El emprendedor no nace se hace"
                </div>
            </td>
        </tr>
    </table>
</div>