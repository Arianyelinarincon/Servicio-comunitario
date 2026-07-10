<?php
// ========== PERMITIR AJAX SIN SESIÓN ESTRICTA ==========
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$es_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!$es_ajax && !isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = [];

// ========== TODOS LOS ESTADOS DE VENEZUELA ==========
$todos_los_estados = [
    'Amazonas', 'Anzoátegui', 'Apure', 'Aragua', 'Barinas', 'Bolívar',
    'Carabobo', 'Cojedes', 'Delta Amacuro', 'Distrito Capital', 'Falcón',
    'Guárico', 'Lara', 'Mérida', 'Miranda', 'Monagas', 'Nueva Esparta',
    'Portuguesa', 'Sucre', 'Táchira', 'Trujillo', 'La Guaira', 'Yaracuy', 'Zulia'
];

// ========== TODOS LOS MUNICIPIOS POR ESTADO ==========
$todos_los_municipios = [
    'Amazonas' => ['Alto Orinoco', 'Atabapo', 'Atures', 'Autana', 'Manapiare', 'Maroa', 'Río Negro'],
    'Anzoátegui' => ['Anaco', 'Aragua', 'Bolívar', 'Bruzual', 'Cajigal', 'Carvajal', 'Cruz Salmerón Acosta', 'Diego Bautista Urbaneja', 'Freites', 'Guanipa', 'Guanta', 'Independencia', 'José Gregorio Monagas', 'Juan Antonio Sotillo', 'Juan Manuel Cajigal', 'Libertad', 'Manuel Ezequiel Bruzual', 'Miranda', 'Pedro María Freites', 'Píritu', 'San José de Guanipa', 'San Juan de Capistrano', 'Santa Ana', 'Simón Bolívar', 'Simón Rodríguez', 'Sir Arturo Mc Gregor'],
    'Apure' => ['Achaguas', 'Biruaca', 'Muñoz', 'Páez', 'Pedro Camejo', 'Rómulo Gallegos', 'San Fernando'],
    'Aragua' => ['Bolívar', 'Camatagua', 'Francisco Linares Alcántara', 'Girardot', 'José Ángel Lamas', 'José Félix Ribas', 'José Rafael Revenga', 'Libertador', 'Mario Briceño Iragorry', 'Ocumare de la Costa de Oro', 'San Casimiro', 'San Sebastián', 'Santiago Mariño', 'Santos Michelena', 'Sucre', 'Tovar', 'Urdaneta', 'Zamora'],
    'Barinas' => ['Alberto Arvelo Torrealba', 'Andrés Eloy Blanco', 'Antonio José de Sucre', 'Arismendi', 'Barinas', 'Bolívar', 'Cruz Paredes', 'Ezequiel Zamora', 'José Félix Ribas', 'José Ignacio del Pumar', 'Mario Briceño Iragorry', 'Obispos', 'Pedraza', 'Rojas', 'Sosa', 'Sucre', 'Tronconal', 'Zea'],
    'Bolívar' => ['Angostura', 'Caroní', 'Cedeño', 'Churún', 'El Callao', 'Gran Sabana', 'Heres', 'Piar', 'Raúl Leoni', 'Roscio', 'Sifontes', 'Sucre', 'Urdaneta'],
    'Carabobo' => ['Bejuma', 'Carlos Arvelo', 'Diego Ibarra', 'Guacara', 'Juan José Mora', 'Libertador', 'Los Guayos', 'Miranda', 'Montalbán', 'Naguanagua', 'Puerto Cabello', 'San Diego', 'San Joaquín', 'Valencia'],
    'Cojedes' => ['Anzoátegui', 'Falcón', 'Girardot', 'Lima Blanco', 'Pao de San Juan Bautista', 'Ricaurte', 'Rómulo Gallegos', 'San Carlos', 'Tinaco'],
    'Delta Amacuro' => ['Antonio Díaz', 'Casacoima', 'Pedernales', 'Tucupita'],
    'Distrito Capital' => ['Libertador'],
    'Falcón' => ['Acosta', 'Bolívar', 'Buchivacoa', 'Cacique Manaure', 'Carirubana', 'Colina', 'Dabajuro', 'Democracia', 'Falcón', 'Federación', 'Jacura', 'José Laurencio Silva', 'Los Taques', 'Mauroa', 'Miranda', 'Monseñor Iturriza', 'Palmasola', 'Petit', 'Píritu', 'San Francisco', 'Sucre', 'Tocópero', 'Unión', 'Urumaco', 'Zamora'],
    'Guárico' => ['Camaguán', 'Chaguaramas', 'El Socorro', 'Francisco de Miranda', 'José Félix Ribas', 'José Tadeo Monagas', 'Juan Germán Roscio', 'Julián Mellado', 'Las Mercedes', 'Leonardo Infante', 'Municipio Ortiz', 'Pedro Zárate', 'Rómulo Gallegos', 'Santa María de Ipire'],
    'Lara' => ['Andrés Eloy Blanco', 'Crespo', 'Iribarren', 'Jiménez', 'Morán', 'Palavecino', 'Simón Planas', 'Torres', 'Urdaneta'],
    'Mérida' => ['Alberto Adriani', 'Andrés Bello', 'Antonio Pinto Salinas', 'Aricagua', 'Arzobispo Chacón', 'Campo Elías', 'Caracciolo Parra Olmedo', 'Cardenal Quintero', 'Guaraque', 'Julio César Salas', 'Justo Briceño', 'Libertador', 'Miranda', 'Obispo Ramos de Lora', 'Padre Noguera', 'Pueblo Llano', 'Rangel', 'Rivas Dávila', 'Santos Marquina', 'Sucre', 'Tovar', 'Tulio Febres Cordero', 'Zea'],
    'Miranda' => ['Acevedo', 'Andrés Bello', 'Baruta', 'Brión', 'Buroz', 'Carrizal', 'Chacao', 'Cristóbal Rojas', 'El Hatillo', 'Guaicaipuro', 'Independencia', 'Lander', 'Los Salias', 'Páez', 'Paz Castillo', 'Pedro Gual', 'Plaza', 'Simón Bolívar', 'Sucre', 'Urdaneta', 'Zamora'],
    'Monagas' => ['Acosta', 'Aguasay', 'Bolívar', 'Caripe', 'Cedeño', 'Ezequiel Zamora', 'Libertador', 'Maturín', 'Piar', 'Punceres', 'San Antonio de Capayacuar', 'Sotillo', 'Uracoa'],
    'Nueva Esparta' => ['Antolín del Campo', 'Arismendi', 'Díaz', 'García', 'Gómez', 'Maneiro', 'Marcano', 'Mariño', 'Península de Macanao', 'Tubores', 'Villalba'],
    'Portuguesa' => ['Agua Blanca', 'Araure', 'Esteller', 'Guanare', 'Guanarito', 'Monseñor José Vicente de Unda', 'Ospino', 'Páez', 'Papelón', 'San Genaro de Boconoíto', 'San Rafael de Onoto', 'Santa Rosalía', 'Sucre', 'Turén'],
    'Sucre' => ['Andrés Eloy Blanco', 'Andrés Mata', 'Arismendi', 'Benítez', 'Bermúdez', 'Bolívar', 'Cajigal', 'Cruz Salmerón Acosta', 'Libertador', 'Mariño', 'Mejía', 'Montes', 'Ribero', 'Sucre', 'Valdez'],
    'Táchira' => ['Andrés Bello', 'Antonio Rómulo Costa', 'Ayacucho', 'Bolívar', 'Cárdenas', 'Córdoba', 'Fernández Feo', 'Francisco de Miranda', 'García de Hevia', 'Guásimos', 'Independencia', 'Jáuregui', 'José María Vargas', 'Junín', 'Libertad', 'Libertador', 'Lobatera', 'Michelena', 'Panamericano', 'Pedro María Ureña', 'Rafael Urdaneta', 'Samuel Darío Maldonado', 'San Cristóbal', 'San Judas Tadeo', 'Seboruco', 'Simón Rodríguez', 'Sucre', 'Torbes', 'Uribante'],
    'Trujillo' => ['Andrés Bello', 'Boconó', 'Bolívar', 'Candelaria', 'Carache', 'Escuque', 'José Felipe Márquez Cañizales', 'Juan Vicente Campos Elías', 'La Ceiba', 'Miranda', 'Monte Carmelo', 'Motatán', 'Pampán', 'Pampanito', 'Rafael Rangel', 'San Rafael de Carvajal', 'Sucre', 'Trujillo', 'Urdaneta', 'Valera'],
    'La Guaira' => ['Vargas'],
    'Yaracuy' => ['Arístides Bastidas', 'Bolívar', 'Bruzual', 'Cocorote', 'Independencia', 'José Antonio Páez', 'La Trinidad', 'Manuel Monge', 'Nirgua', 'Peña', 'San Felipe', 'Sucre', 'Urachiche', 'Veroes'],
    'Zulia' => ['Almirante Padilla', 'Baralt', 'Cabimas', 'Catatumbo', 'Colón', 'Francisco Javier Pulgar', 'Jesús Enrique Lossada', 'Jesús María Semprún', 'La Cañada de Urdaneta', 'Lagunillas', 'Machiques de Perijá', 'Mara', 'Maracaibo', 'Miranda', 'Páez', 'Rosario de Perijá', 'San Francisco', 'Santa Rita', 'Simón Bolívar', 'Sucre', 'Valmore Rodríguez']
];

// ========== TODAS LAS PARROQUIAS POR MUNICIPIO ==========
$todas_las_parroquias = [
    // ZULIA - Maracaibo
    'Maracaibo' => ['Bolívar', 'Cecilio Acosta', 'Chiquinquirá', 'Coquivacoa', 'Idelfonso Vásquez', 'Juana de Ávila', 'Luis Hurtado Higuera', 'Manuel Dagnino', 'Olegario Villalobos', 'Raúl Leoni', 'Santa Lucía', 'San Isidro', 'Venancio Pulgar'],
    // ZULIA - San Francisco
    'San Francisco' => ['Domitila Flores', 'El Bajo', 'Francisco Ochoa', 'José Domínguez', 'Los Cortijos', 'San Francisco'],
    // ZULIA - Cabimas
    'Cabimas' => ['Ambrosio', 'Carmen Herrera', 'Germán Ríos Linares', 'La Rosa', 'Punta Gorda', 'Rómulo Betancourt'],
    // ZULIA - Santa Rita
    'Santa Rita' => ['El Mene', 'Pedro Lucas Urribarrí', 'Santa Rita'],
    // ZULIA - Jesús Enrique Lossada
    'Jesús Enrique Lossada' => ['La Concepción', 'San José', 'Los Cortijos'],
    // ZULIA - La Cañada de Urdaneta
    'La Cañada de Urdaneta' => ['Andrés Bello', 'La Cañada', 'Simón Bolívar'],
    // ZULIA - Lagunillas
    'Lagunillas' => ['Alonso de Ojeda', 'Campo Lara', 'El Danto', 'La Rosa', 'Libertad', 'Venezuela'],
    // ZULIA - Machiques de Perijá
    'Machiques de Perijá' => ['Bartolomé de las Casas', 'San José de Perijá'],
    // ZULIA - Mara
    'Mara' => ['Los Olivitos', 'San Rafael', 'Santa Cruz de Mara'],
    // ZULIA - Miranda
    'Miranda' => ['Ana María Campos', 'Faria', 'San Antonio', 'San José'],
    // ZULIA - Rosario de Perijá
    'Rosario de Perijá' => ['El Rosario', 'Monte Carmelo'],
    // ZULIA - Simón Bolívar
    'Simón Bolívar' => ['Rafael Urdaneta', 'San José', 'Tía Juana'],
    // ZULIA - Sucre
    'Sucre' => ['Bobures', 'Gibraltar', 'Monsignor', 'Rómulo Gallegos'],
    // ZULIA - Valmore Rodríguez
    'Valmore Rodríguez' => ['La Victoria', 'Rafael Urdaneta', 'San Timoteo'],
    // ZULIA - Almirante Padilla
    'Almirante Padilla' => ['El Toro', 'Los Puertos de Altagracia'],
    // ZULIA - Baralt
    'Baralt' => ['General Urdaneta', 'Libertador', 'Monseñor', 'San Timoteo'],
    // ZULIA - Catatumbo
    'Catatumbo' => ['Encontrados', 'Rafael Urdaneta'],
    // ZULIA - Colón
    'Colón' => ['San Carlos del Zulia', 'Santa Bárbara'],
    // ZULIA - Francisco Javier Pulgar
    'Francisco Javier Pulgar' => ['Pulgar'],

    // ========== LARA ==========
    'Barquisimeto' => ['Catedral', 'Concepción', 'El Cují', 'Juan de Villegas', 'Santa Rosa', 'Tamaca', 'Unión'],
    'Cabudare' => ['Cabudare', 'José Gregorio Bastidas'],
    'Carora' => ['Carora', 'San Juan', 'Torres'],
    'El Tocuyo' => ['El Tocuyo', 'San Miguel'],
    'Quíbor' => ['Quíbor', 'San Pedro'],

    // ========== CARABOBO ==========
    'Valencia' => ['Candelaria', 'Catedral', 'El Socorro', 'Miguel Peña', 'Rafael Urdaneta', 'San Blas', 'San José', 'Santa Rosa'],
    'Puerto Cabello' => ['Borburata', 'Puerto Cabello', 'Salom', 'Santa Rosa', 'Urama'],
    'Guacara' => ['Ciudad Alianza', 'Guacara', 'Yagua'],
    'Naguanagua' => ['Naguanagua', 'Nueva Valencia'],
    'San Diego' => ['San Diego'],

    // ========== MIRANDA ==========
    'Baruta' => ['Baruta', 'El Cafetal', 'La Trinidad'],
    'Chacao' => ['Chacao'],
    'El Hatillo' => ['El Hatillo'],
    'Los Salias' => ['San Antonio de Los Altos'],
    'Sucre' => ['Caucagüita', 'La Dolorita', 'Leoncio Martínez', 'Petare'],

    // ========== DISTRITO CAPITAL ==========
    'Libertador' => ['23 de Enero', 'Altagracia', 'Antímano', 'Candelaria', 'Caricuao', 'Catedral', 'Coche', 'El Paraíso', 'El Recreo', 'El Valle', 'La Candelaria', 'La Pastora', 'La Vega', 'Macarao', 'San Agustín', 'San Bernardino', 'San José', 'San Juan', 'San Pedro', 'Santa Rosalía', 'Santa Teresa', 'Sucre'],

    // ========== BOLÍVAR ==========
    'Heres' => ['Agua Salada', 'Catedral', 'José Antonio Páez', 'La Sabanita', 'Simón Bolívar', 'Vista Hermosa'],
    'Caroní' => ['Cachamay', 'Chirica', 'Dalla Costa', 'Once de Abril', 'Pozo Verde', 'San Félix', 'Simón Bolívar', 'Unare', 'Universidad', 'Vista al Sol'],
    'Gran Sabana' => ['Gran Sabana', 'Ikabaru'],

    // ========== ANZOÁTEGUI ==========
    'Barcelona' => ['Barcelona', 'El Carmen', 'Naricual', 'San Cristóbal'],
    'Puerto La Cruz' => ['Puerto La Cruz', 'Pozuelos'],
    'Lechería' => ['Lechería'],
    'El Tigre' => ['El Tigre', 'Mamo'],

    // ========== MONAGAS ==========
    'Maturín' => ['Alto de Los Godos', 'Boquerón', 'Las Cocuizas', 'Maturín', 'San Simón', 'Santa Cruz'],
    'Caripe' => ['Caripe', 'El Guácharo'],

    // ========== FALCÓN ==========
    'Coro' => ['Acosta', 'Coro', 'Gavilán', 'San Antonio', 'San Gabriel'],
    'Punto Fijo' => ['Punto Fijo', 'Santa Ana'],

    // ========== SUCRE ==========
    'Cumaná' => ['Cumaná', 'San Luis', 'Santa Inés'],
    'Carúpano' => ['Carúpano', 'Santa Catalina'],

    // ========== TÁCHIRA ==========
    'San Cristóbal' => ['Catedral', 'La Concordia', 'San Sebastián', 'Santa Ana'],
    'Ureña' => ['Ureña'],
    'Rubio' => ['Rubio'],

    // ========== MÉRIDA ==========
    'Mérida' => ['Ariás', 'El Llano', 'Mérida', 'Sagrario', 'Spinetti Dini', 'Zea'],

    // ========== PORTUGUESA ==========
    'Guanare' => ['Guanare', 'San José de la Montaña'],

    // ========== ARAGUA ==========
    'Maracay' => ['Maracay', 'San Vicente'],

    // ========== BARINAS ==========
    'Barinas' => ['Barinas', 'Domingo Obispo'],

    // ========== APURE ==========
    'San Fernando de Apure' => ['San Fernando', 'San Rafael de Atamaica'],

    // ========== COJEDES ==========
    'San Carlos' => ['San Carlos', 'Tinaco'],

    // ========== DELTA AMACURO ==========
    'Tucupita' => ['Tucupita'],

    // ========== GUÁRICO ==========
    'Calabozo' => ['Calabozo', 'El Calvario'],

    // ========== NUEVA ESPARTA ==========
    'Porlamar' => ['Porlamar'],

    // ========== YARACUY ==========
    'San Felipe' => ['San Felipe'],

    // ========== TRUJILLO ==========
    'Valera' => ['Valera'],

    // ========== LA GUAIRA ==========
    'La Guaira' => ['La Guaira']
];

switch ($action) {
    case 'get_paises':
        $response[] = ['nombre' => 'Venezuela'];
        break;

    case 'get_estados':
        foreach ($todos_los_estados as $estado) {
            $response[] = ['nombre' => $estado];
        }
        break;

    case 'get_municipios':
        $estado = $_GET['estado'] ?? '';
        if (!empty($estado) && isset($todos_los_municipios[$estado])) {
            foreach ($todos_los_municipios[$estado] as $municipio) {
                $response[] = ['nombre' => $municipio];
            }
        }
        break;

    case 'get_parroquias':
        $municipio = $_GET['municipio'] ?? '';
        if (!empty($municipio) && isset($todas_las_parroquias[$municipio])) {
            foreach ($todas_las_parroquias[$municipio] as $parroquia) {
                $response[] = ['nombre' => $parroquia];
            }
        }
        break;

    default:
        $response = ['error' => 'Acción no válida'];
}

echo json_encode($response);
?>