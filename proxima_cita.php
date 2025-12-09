<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * FILE: proxima_cita.php
 * ════════════════════════════════════════════════════════════════════════════════
 * DESCRIPCIÓN: API endpoint JSON para obtener la próxima cita de un paciente
 * Retorna datos de la cita más próxima en el futuro (fecha >= NOW())
 * FUNCIONALIDAD: GET request → Query SQL → JSON response
 * DEPENDENCIAS: MySQLi db.php (conexión a base de datos)
 * MÉTODOS: GET (idPaciente como parámetro)
 * RESPUESTAS: JSON con detalles de cita o error
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * PARÁMETROS:
 * - idPaciente (GET): ID del paciente (obligatorio)
 * 
 * RESPUESTAS POSIBLES:
 * 1. SUCCESS (200): Cita encontrada
 *    {
 *      "ID_Cita": 42,
 *      "Fecha_Cita": "2024-12-15 14:30:00",
 *      "Nombre_Especialidad": "Psicología Clínica",
 *      "Estado": "Confirmada",
 *      "Duracion": "60",
 *      "Nombre_Psicologo": "Carlos",
 *      "Apellido_Psicologo": "López"
 *    }
 * 
 * 2. NO CITAS (200): Sin citas próximas
 *    { "message": "No hay próximas citas" }
 * 
 * 3. MISSING PARAM (200): Falta parámetro
 *    { "error": "Falta el ID del paciente" }
 * 
 * 4. SQL ERROR (200): Error en consulta
 *    { "error": "Error al preparar la consulta: ..." }
 * 
 * LÓGICA SQL:
 * - INNER JOIN psicologos: Obtener info del psicólogo (obligatorio)
 * - LEFT JOIN especialidades: Obtener especialidad (opcional)
 * - WHERE Fecha_Cita >= NOW(): Solo citas futuras
 * - ORDER BY Fecha_Cita ASC: Ordena por fecha
 * - LIMIT 1: Solo la próxima cita
 * 
 * SEGURIDAD:
 * - Prepared statement con bind_param previene SQL injection
 * - Parámetro idPaciente es tipado como integer
 * - JSON response previene XSS
 */

// Establecer content-type como JSON
header("Content-Type: application/json");

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN Y CONEXIÓN A BASE DE DATOS
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Incluir conexión centralizada a la base de datos
 * 
 * FLUJO:
 * 1. include "db.php" carga el archivo de configuración
 * 2. db.php establece la conexión MySQLi en variable $mysqli
 * 3. La conexión ya está autenticada y lista para usar
 * 
 * PROPÓSITO: Reutilizar conexión centralizada en lugar de crear nueva
 * Mejora seguridad y rendimiento (una sola conexión por request)
 * 
 * VARIABLE GLOBAL:
 * - $mysqli: Objeto MySQLi con conexión activa a base de datos
 */

include "db.php";   // Proporciona $mysqli para consultas

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: VALIDACIÓN DE PARÁMETROS
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Validar que el parámetro idPaciente esté presente en GET
 * 
 * FLUJO:
 * 1. Obtiene $_GET['idPaciente'] si existe, null si no
 * 2. Verifica que no esté vacío
 * 3. Si falta: retorna error JSON y termina
 * 4. Si existe: continúa con consulta
 * 
 * PROPÓSITO: Proteger contra requests inválidos
 * El idPaciente es obligatorio para filtrar citas
 * 
 * ERROR RESPONSE:
 * { "error": "Falta el ID del paciente" }
 */

// Verificar que el parámetro idPaciente esté presente
$idPaciente = $_GET['idPaciente'] ?? null;

// Si no viene el parámetro, retornar error
if (!$idPaciente) {
    echo json_encode(["error" => "Falta el ID del paciente"]);
    exit;
}


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: CONSTRUCCIÓN DE CONSULTA SQL
// ──────────────────────────────────────────────────────────────────────────────
/**
 * SQL Query para obtener la próxima cita de un paciente
 * 
 * ESTRUCTURA:
 * SELECT: Selecciona campos específicos de 3 tablas (citas, psicologos, especialidades)
 * FROM: citas (tabla principal)
 * INNER JOIN psicologos: Obtener detalles del psicólogo (relación obligatoria)
 * LEFT JOIN especialidades: Obtener especialidad (opcional, puede ser NULL)
 * WHERE: Filtrar por:
 *   - ID_Paciente: Solo citas de este paciente
 *   - Fecha_Cita >= NOW(): Solo citas futuras (no pasadas)
 * ORDER BY: Fecha_Cita ASC (próxima primero)
 * LIMIT 1: Solo la cita más próxima
 * 
 * CAMPOS RETORNADOS:
 * - ID_Cita: Identificador único de la cita
 * - Fecha_Cita: Fecha y hora de la cita (formato: YYYY-MM-DD HH:MM:SS)
 * - Nombre_Especialidad: Especialidad del psicólogo (alias de e.Nombre_Especialidad)
 * - Estado: Estado de la cita (Confirmada, Pendiente, etc.)
 * - Duracion: Duración en minutos
 * - Nombre_Psicologo: Nombre del psicólogo
 * - Apellido_Psicologo: Apellido del psicólogo
 * 
 * JOINS EXPLICADOS:
 * - INNER JOIN psicologos: Cita siempre tiene un psicólogo asociado
 *   Filtra citas sin psicólogo (inconsistencia de datos)
 * - LEFT JOIN especialidades: No todas las especialidades pueden estar completas
 *   LEFT permite NULL si no existe especialidad
 * 
 * CONDICIONES:
 * - c.ID_Paciente = ?: ID del paciente (parámetro)
 * - c.Fecha_Cita >= NOW(): Solo futuras (hoy en adelante)
 *   NOW() es timestamp actual del servidor
 * 
 * PROPÓSITO: Obtener una sola cita: la más próxima en el futuro
 * Se usa en Perfil.php para mostrar próxima cita en dashboard
 */

// Consulta de la próxima cita
$sql = "
SELECT 
    c.ID_Cita,
    c.Fecha_Cita,
    e.Nombre_Especialidad AS Nombre_Especialidad,
    c.Estado,
    c.Duracion,
    p.Nombre_Psicologo,
    p.Apellido_Psicologo
FROM citas c
INNER JOIN psicologos p ON c.ID_Psicologo = p.ID_Psicologo
LEFT JOIN especialidades e ON p.ID_Especialidad = e.ID_Especialidad
WHERE c.ID_Paciente = ?
  AND c.Fecha_Cita >= NOW()
ORDER BY c.Fecha_Cita ASC
LIMIT 1
";


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: PREPARACIÓN Y EJECUCIÓN DEL PREPARED STATEMENT
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Preparar statement y ejecutar consulta SQL de forma segura
 * 
 * FLUJO:
 * 1. $mysqli->prepare($sql): Prepara la consulta (compila, valida sintaxis)
 * 2. Verifica que prepare() no falle (!$stmt)
 * 3. $stmt->bind_param("i", $idPaciente): Vincula parámetro
 *    - "i" = integer (tipo de $idPaciente)
 *    - Previene SQL injection (escapa valores)
 * 4. $stmt->execute(): Ejecuta la consulta con parámetros vinculados
 * 5. $stmt->get_result(): Obtiene resultado como objeto MySQLi_Result
 * 
 * SEGURIDAD:
 * - Prepared statements: SQL injection prevention
 * - bind_param con tipos: Validación de tipos
 * - Parámetro separado del SQL: No se concatenan strings
 * 
 * ERROR HANDLING:
 * - Si prepare() falla: retorna error JSON
 * - Si execute() falla: se captura con get_result()
 * 
 * VENTAJAS:
 * - Compilación separada de datos
 * - Reutilización de statement
 * - Rendimiento mejorado
 * 
 * PROPÓSITO: Ejecutar consulta de forma segura y eficiente
 */

// Preparar el prepared statement
$stmt = $mysqli->prepare($sql);

// Validar que la preparación fue exitosa
if (!$stmt) {
    // Si falla la preparación, retornar error
    echo json_encode(["error" => "Error al preparar la consulta: " . $mysqli->error]);
    exit;
}

// Vincular parámetros: "i" = integer para $idPaciente
$stmt->bind_param("i", $idPaciente);

// Ejecutar la consulta preparada
$stmt->execute();

// Obtener resultado como objeto MySQLi_Result
$result = $stmt->get_result();


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: PROCESAMIENTO DE RESULTADOS Y RESPUESTA JSON
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Validar resultados y retornar JSON apropiado
 * 
 * FLUJO:
 * 1. $result->num_rows: Cantidad de filas retornadas
 * 2. Si num_rows === 0: No hay citas próximas
 *    - Retorna: { "message": "No hay próximas citas" }
 *    - Exit: Termina la ejecución
 * 3. Si num_rows > 0: Cita encontrada
 *    - $result->fetch_assoc(): Obtiene fila como array asociativo
 *    - json_encode($cita): Convierte a JSON
 *    - Echo: Retorna al cliente
 * 
 * DATOS RETORNADOS (en caso exitoso):
 * {
 *   "ID_Cita": 42,
 *   "Fecha_Cita": "2024-12-15 14:30:00",
 *   "Nombre_Especialidad": "Psicología Clínica",
 *   "Estado": "Confirmada",
 *   "Duracion": "60",
 *   "Nombre_Psicologo": "Carlos",
 *   "Apellido_Psicologo": "López"
 * }
 * 
 * TIPOS DE RESPUESTA:
 * 1. Cita encontrada: Array con datos de cita
 * 2. Sin citas: { "message": "No hay próximas citas" }
 * 3. Error parámetro: { "error": "Falta el ID del paciente" }
 * 4. Error SQL: { "error": "Error al preparar la consulta: ..." }
 * 
 * PROPÓSITO: Retornar JSON que el cliente pueda procesar
 * Se usa en Perfil.php via fetch() para mostrar próxima cita
 * 
 * SEGURIDAD:
 * - json_encode(): Escapa caracteres especiales (XSS prevention)
 * - Nunca se retorna código SQL completo (solo errores generales)
 */

// Validar si hay resultados
if ($result->num_rows === 0) {
    // No hay citas próximas
    echo json_encode(["message" => "No hay próximas citas"]);
    exit;
}

// Obtener la cita como array asociativo
$cita = $result->fetch_assoc();

// Retornar JSON con datos de la cita
echo json_encode($cita);
?>