Crear módulo llamado Asistencias, con los siguientes permisos: Ver Asistencias, Registrar Asistencias

CONTEXTO:
- Sistema de gestión de asistencias para usuarios agrupados
- Tabla principal: users_grupo (contiene la relación entre usuarios y grupos)
- La tabla users_grupo tiene relación con users para obtener datos del alumno
- Solo se registran excepciones (ausencias y llegadas tarde)
- Si no hay registro, se asume que el usuario estuvo presente
- IMPORTANTE: Las asistencias se organizan por CORTES (Corte 1, Corte 2, Corte 3, Corte 4)

MIGRACIÓN Y MODELO:

Crear tabla 'asistencias':
- id, user_id (FK a users), grupo_id (FK a users_grupo), fecha (date)
- corte (enum: 'corte_1', 'corte_2', 'corte_3', 'corte_4')
- estado (enum: 'ausencia_justificada', 'ausencia_injustificada', 'tarde_justificada', 'tarde_injustificada')
- justificacion (text, nullable), hora_registro (time, nullable)
- Índice único: user_id + fecha + corte

ENDPOINTS REQUERIDOS:

1. GET grupos/{grupo_id}/usuarios
   - Obtener usuarios desde users_grupo con relación a users
   - Retornar: id, nombre, email de cada usuario del grupo
   - Ya existe un services para obtener usuarios de un grupo

2. GET asistencias/grupo/{grupo_id}/fecha/{fecha}/corte/{corte}
   - Obtener solo excepciones registradas para esa fecha y corte específico
   - Parámetros: grupo_id, fecha, corte (corte_1, corte_2, corte_3, corte_4)
   - Retornar: id, user_id, estado, justificacion, hora_registro
   - Array vacío si no hay excepciones

3. POST asistencias/registrar-grupo
   - Recibir: grupo_id, fecha, corte, excepciones [{user_id, estado, justificacion?, hora_registro?}]
   - Validar que corte sea válido (corte_1, corte_2, corte_3, corte_4)
   - Validar que user_id existan en users_grupo para ese grupo
   - Validar justificacion obligatoria para estados justificados
   - Validar hora_registro obligatoria para llegadas tarde
   - Usar transacciones
   - No permitir duplicados: user_id + fecha + corte

4. PUT asistencias/{id}
   - Actualizar estado, justificacion u hora de registro
   - Validar según el nuevo estado
   - No permitir cambiar corte

5. DELETE asistencias/{id}
   - Eliminar excepción (convierte en presente para ese corte)

6. GET asistencias/reporte/{grupo_id}/corte/{corte}
   - Parámetros: fecha_inicio, fecha_fin, corte
   - Calcular por cada usuario en users_grupo para el corte especificado:
     * Días presente, ausencias J/I, tardes J/I, porcentaje asistencia
   - Incluir totales del grupo

7. GET asistencias/reporte-general/{grupo_id}
   - Parámetros: fecha_inicio, fecha_fin
   - Generar reporte consolidado de TODOS los cortes
   - Por cada usuario mostrar estadísticas separadas por corte
   - Incluir promedio general de asistencia

8. GET endpoint para obtener los periodos lectivos.

9. Get endpoint para obtener los grupos agrupado por turnos al recibir un periodo lectivo.

10. Crear endpoint para exportar los reportes a pdf y excel

VALIDACIONES:
- fecha: no futura
- corte: debe ser uno de (corte_1, corte_2, corte_3, corte_4)
- estados justificados: justificacion obligatoria
- llegadas tarde: hora_registro obligatoria
- user_id debe existir en users_grupo para el grupo_id
- No permitir duplicado de user_id + fecha + corte

Crear la documentacion detallada de los endpoint y los parametros que reciben. Incluir en la Auditoria los modelos.
