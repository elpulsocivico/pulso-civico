<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * Catálogo histórico de legislaturas del H. Congreso del Estado de Chiapas.
 * Incluye LXVIII (2021-2024), LXVII (2018-2021) y cronología de legislaturas.
 * Fuentes oficiales: congresochiapas.gob.mx e iepc-chiapas.org.mx.
 */
final class CongresoChiapasCatalogoHistorico
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputados68(): array
    {
        return [
            // MAYORÍA RELATIVA (24 Distritos)
            ['nombre' => 'Felipe de Jesús Granda Pastrana', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 1', 'cabecera' => 'Tuxtla Gutiérrez Oriente'],
            ['nombre' => 'Flor de María Esponda Torres', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 2', 'cabecera' => 'Tuxtla Gutiérrez Poniente'],
            ['nombre' => 'María Reyes Diego Gómez', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 3', 'cabecera' => 'Chiapa de Corzo'],
            ['nombre' => 'Jorge Luis Villatoro Osorio', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 4', 'cabecera' => 'Yajalón'],
            ['nombre' => 'Cuauhtémoc Manuel Hernández Sánchez', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 5', 'cabecera' => 'San Cristóbal de Las Casas'],
            ['nombre' => 'Verónica Alcázar Cordero', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 6', 'cabecera' => 'Comitán de Domínguez'],
            ['nombre' => 'María de los Ángeles Trejo Huerta', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 7', 'cabecera' => 'Ocosingo'],
            ['nombre' => 'Martha Verónica Alcázar Cordero', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 8', 'cabecera' => 'Simojovel'],
            ['nombre' => 'Carlos Morelos Rodríguez', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 9', 'cabecera' => 'Palenque'],
            ['nombre' => 'José Antonio Aguilar Meza', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 10', 'cabecera' => 'La Trinitaria'],
            ['nombre' => 'Karina Margarita del Río Zenteno', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 11', 'cabecera' => 'Bochil'],
            ['nombre' => 'Agustín Ruiz Mendoza', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 12', 'cabecera' => 'Pichucalco'],
            ['nombre' => 'Paola Villamonte Pérez', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 13', 'cabecera' => 'Tuxtla Gutiérrez Sur'],
            ['nombre' => 'Lina Nango Molina', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 14', 'cabecera' => 'Cintalapa'],
            ['nombre' => 'Faride Abud García', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 15', 'cabecera' => 'Tonalá'],
            ['nombre' => 'Yolanda del Rosario Correa González', 'partido' => 'CHIAPAS UNIDO', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 16', 'cabecera' => 'Huixtla'],
            ['nombre' => 'Jorge Luis Llaven Abarca', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 17', 'cabecera' => 'Motozintla'],
            ['nombre' => 'Enrique Zamora Morlet', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 18', 'cabecera' => 'Tapachula Norte'],
            ['nombre' => 'Yamil Melgar Bravo', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 19', 'cabecera' => 'Tapachula Sur'],
            ['nombre' => 'Cecilia López Sánchez', 'partido' => 'PODEMOS MOVER A CHIAPAS', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 20', 'cabecera' => 'Las Margaritas'],
            ['nombre' => 'Martha Guadalupe Martínez Ruiz', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 21', 'cabecera' => 'Venustiano Carranza'],
            ['nombre' => 'Mario Humberto Vázquez López', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 22', 'cabecera' => 'Chamula'],
            ['nombre' => 'Zurisaddai Aguilar Flores', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 23', 'cabecera' => 'Villaflores'],
            ['nombre' => 'Elizabeth Escobedo Morales', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 24', 'cabecera' => 'Chilón'],

            // REPRESENTACIÓN PROPORCIONAL (16 Plurinominales)
            ['nombre' => 'Aarón Yamil Melgar Bravo', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Petrona de la Cruz Cruz', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Marcelo Toledo Cruz', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Sonia Catalina Álvarez', 'partido' => 'PT', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Carlos Mario Estrada Urbina', 'partido' => 'PT', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Rocío Guadalupe Cervantes Cancino', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Luis Armando Córdova Díaz', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Citlaly Isabel de León Villard', 'partido' => 'PES', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Rubén Antonio Zuarth Esquinca', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Leticia Albores Ruíz', 'partido' => 'RSP', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Sandra Cecilia Herrera Domínguez', 'partido' => 'RSP', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Carolina Zuarth Ramos', 'partido' => 'CHIAPAS UNIDO', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Leticia Méndez Intzin', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Sergio David Molina Gómez', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'María Luiza López Sánchez', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Fabiola Ricci Diestel', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputados67(): array
    {
        return [
            // MAYORÍA RELATIVA (24 Distritos)
            ['nombre' => 'Marcelo Toledo Cruz', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 1', 'cabecera' => 'Tuxtla Gutiérrez Oriente'],
            ['nombre' => 'Jorge Jhonattan Molina Morales', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 2', 'cabecera' => 'Tuxtla Gutiérrez Poniente'],
            ['nombre' => 'Juan Pablo Montes de Oca Avendaño', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 3', 'cabecera' => 'Chiapa de Corzo'],
            ['nombre' => 'Flor de María Guirao Aguilar', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 4', 'cabecera' => 'Yajalón'],
            ['nombre' => 'Juan Salvador Camacho Velasco', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 5', 'cabecera' => 'San Cristóbal de Las Casas'],
            ['nombre' => 'Rosa Netro Rodríguez', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 6', 'cabecera' => 'Comitán de Domínguez'],
            ['nombre' => 'José Octavio García Macías', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 7', 'cabecera' => 'Ocosingo'],
            ['nombre' => 'Rosa Elizabeth Bonilla Hidalgo', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 8', 'cabecera' => 'Simojovel'],
            ['nombre' => 'Emilio Enrique Salazar Farías', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 9', 'cabecera' => 'Palenque'],
            ['nombre' => 'Tania Guadalupe Martínez Forsland', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 10', 'cabecera' => 'La Trinitaria'],
            ['nombre' => 'Iris Adriana Aguilar Pavón', 'partido' => 'CHIAPAS UNIDO', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 11', 'cabecera' => 'Bochil'],
            ['nombre' => 'Dulce María Rodríguez Ovando', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 12', 'cabecera' => 'Pichucalco'],
            ['nombre' => 'Adriana Bustamante Castellanos', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 13', 'cabecera' => 'Tuxtla Gutiérrez Sur'],
            ['nombre' => 'Fidel Álvarez Toledo', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 14', 'cabecera' => 'Cintalapa'],
            ['nombre' => 'Miguel Ángel Córdova Ochoa', 'partido' => 'CHIAPAS UNIDO', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 15', 'cabecera' => 'Tonalá'],
            ['nombre' => 'Olvey Palomeque Pineda', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 16', 'cabecera' => 'Huixtla'],
            ['nombre' => 'Patricia Mass Lazos', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 17', 'cabecera' => 'Motozintla'],
            ['nombre' => 'Cinthya Vianney Reyes Sumuano', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 18', 'cabecera' => 'Tapachula Norte'],
            ['nombre' => 'Sergio Rivas Vázquez', 'partido' => 'PES', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 19', 'cabecera' => 'Tapachula Sur'],
            ['nombre' => 'Rosa Valdivieso Hidalgo', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 20', 'cabecera' => 'Las Margaritas'],
            ['nombre' => 'María Elena Villatoro Culebro', 'partido' => 'CHIAPAS UNIDO', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 21', 'cabecera' => 'Venustiano Carranza'],
            ['nombre' => 'Mario Santiz Gómez', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 22', 'cabecera' => 'Chamula'],
            ['nombre' => 'Eduardo Francisco Zenteno Núñez', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 23', 'cabecera' => 'Villaflores'],
            ['nombre' => 'Carolina Elizabeth Sohle Gómez', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 24', 'cabecera' => 'Chilón'],

            // REPRESENTACIÓN PROPORCIONAL (16 Plurinominales)
            ['nombre' => 'Dulce Consuelo Gallegos Mijangos', 'partido' => 'PODEMOS MOVER A CHIAPAS', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Valeria Santiago Barrientos', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Maya de León Villard', 'partido' => 'PES', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Luz María Palacios Farrera', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Aída Guadalupe Jiménez Sesma', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Janette Ovando Reazola', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Silvia Torreblanca Alfaro', 'partido' => 'PES', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Ana Laura Romero Basurto', 'partido' => 'PODEMOS MOVER A CHIAPAS', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Ricardo Zepeda Gutiérrez', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Nairobi Ojeda Arellano', 'partido' => 'PT', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Haydeé Ocampo Olvera', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Nolberto Farfán Solís', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Kalyanamaya de León Villard', 'partido' => 'PES', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'María Obdulia Megchún López', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Flor de María de Jesús Ocampo', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'José Manuel Velasco Siles', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getCronologiaHistorica(): array
    {
        return [
            ['numero' => 69, 'romano' => 'LXIX', 'periodo' => '2024-2027', 'total_diputados' => 40, 'mr' => 24, 'rp' => 16, 'estado' => 'En funciones'],
            ['numero' => 68, 'romano' => 'LXVIII', 'periodo' => '2021-2024', 'total_diputados' => 40, 'mr' => 24, 'rp' => 16, 'estado' => 'Concluida'],
            ['numero' => 67, 'romano' => 'LXVII', 'periodo' => '2018-2021', 'total_diputados' => 40, 'mr' => 24, 'rp' => 16, 'estado' => 'Concluida'],
            ['numero' => 66, 'romano' => 'LXVI', 'periodo' => '2015-2018', 'total_diputados' => 41, 'mr' => 24, 'rp' => 17, 'estado' => 'Concluida'],
            ['numero' => 65, 'romano' => 'LXV', 'periodo' => '2012-2015', 'total_diputados' => 41, 'mr' => 24, 'rp' => 17, 'estado' => 'Concluida'],
            ['numero' => 64, 'romano' => 'LXIV', 'periodo' => '2010-2012', 'total_diputados' => 40, 'mr' => 24, 'rp' => 16, 'estado' => 'Concluida'],
            ['numero' => 63, 'romano' => 'LXIII', 'periodo' => '2007-2010', 'total_diputados' => 40, 'mr' => 24, 'rp' => 16, 'estado' => 'Concluida'],
            ['numero' => 62, 'romano' => 'LXII', 'periodo' => '2004-2007', 'total_diputados' => 40, 'mr' => 24, 'rp' => 16, 'estado' => 'Concluida'],
            ['numero' => 61, 'romano' => 'LXI', 'periodo' => '2001-2004', 'total_diputados' => 40, 'mr' => 24, 'rp' => 16, 'estado' => 'Concluida'],
        ];
    }
}
