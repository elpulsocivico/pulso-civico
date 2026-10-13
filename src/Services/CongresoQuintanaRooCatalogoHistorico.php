<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * Catálogo histórico de legislaturas del H. Congreso del Estado de Quintana Roo.
 * Incluye XVII (2022-2024), XVI (2019-2022) y la cronología histórica (Constituyente 1974 a XVIII actual).
 * Fuente: congresoqroo.gob.mx e ieqroo.org.mx.
 */
final class CongresoQuintanaRooCatalogoHistorico
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputados17(): array
    {
        return [
            // MAYORÍA RELATIVA (15)
            ['nombre' => 'Julián Javier Ricalde Magaña', 'partido' => 'FXM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 1', 'cabecera' => 'Kantunilkín'],
            ['nombre' => 'Susana Hurtado Vallejo', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 2', 'cabecera' => 'Cancún'],
            ['nombre' => 'Hugo Alday Nieto', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 3', 'cabecera' => 'Cancún'],
            ['nombre' => 'Cristina del Carmen Alcerreca Manzanero', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 4', 'cabecera' => 'Cancún'],
            ['nombre' => 'Mildred Concepción Ávila Vera', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 5', 'cabecera' => 'Cancún'],
            ['nombre' => 'Andrea del Rosario González Loria', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 6', 'cabecera' => 'Cancún'],
            ['nombre' => 'María Fernanda Cruz Sánchez', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 7', 'cabecera' => 'Cancún'],
            ['nombre' => 'Isaac Janix Alanís', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 8', 'cabecera' => 'Cancún'],
            ['nombre' => 'Silvia Dzul Sánchez', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 9', 'cabecera' => 'Tulum'],
            ['nombre' => 'Angy Estefanía Mercado Asencio', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 10', 'cabecera' => 'Playa del Carmen'],
            ['nombre' => 'Renán Eduardo Sánchez Tajonar', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 11', 'cabecera' => 'Cozumel'],
            ['nombre' => 'José María Chacón Chablé', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 12', 'cabecera' => 'Felipe Carrillo Puerto'],
            ['nombre' => 'Alicia Tapia Montejo', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 13', 'cabecera' => 'Bacalar'],
            ['nombre' => 'Elda María Xix Euán', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 14', 'cabecera' => 'Chetumal rural'],
            ['nombre' => 'Omar Antonio Rodríguez Martínez', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 15', 'cabecera' => 'Chetumal urbano'],

            // REPRESENTACIÓN PROPORCIONAL (10)
            ['nombre' => 'Luis Humberto Aldana Navarro', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Ricardo Velazco Rodríguez', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Luz María Beristain Navarrete', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Yohanet Teódula Torres Muñoz', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'María José Osorio Rosas', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Guillermo Andrés Brahms González', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Cinthya Yamilé Millán Estrella', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Maritza Deyanira Basurto Basurto', 'partido' => 'MC', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Elda Candelaria Ayuso Achach', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Alfonsa Leticia Padilla Medina', 'partido' => 'MAS', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputados16(): array
    {
        return [
            // MAYORÍA RELATIVA (15)
            ['nombre' => 'Teresa Atenea Gómez Ricalde', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 1', 'cabecera' => 'Kantunilkín'],
            ['nombre' => 'Hernán Villatoro Barrios', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 2', 'cabecera' => 'Cancún'],
            ['nombre' => 'Wilbert Alberto Batún Chulim', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 3', 'cabecera' => 'Cancún'],
            ['nombre' => 'Tyara Schleske de Ariño', 'partido' => 'PVEM', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 4', 'cabecera' => 'Cancún'],
            ['nombre' => 'Reyna Arelly Durán Ovando', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 5', 'cabecera' => 'Cancún'],
            ['nombre' => 'Erika Guadalupe Castillo Acosta', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 6', 'cabecera' => 'Cancún'],
            ['nombre' => 'María Fernanda Trejo Quijano', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 7', 'cabecera' => 'Cancún'],
            ['nombre' => 'Edgar Humberto Gasca Arceo', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 8', 'cabecera' => 'Cancún'],
            ['nombre' => 'Euterpe Alicia Gutiérrez Valasis', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 9', 'cabecera' => 'Tulum'],
            ['nombre' => 'Roxana Lili Campos Miranda', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 10', 'cabecera' => 'Playa del Carmen'],
            ['nombre' => 'Carlos Rafael Hernández Blanco', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 11', 'cabecera' => 'Cozumel'],
            ['nombre' => 'Pedro Enrique Pérez Díaz', 'partido' => 'PRD', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 12', 'cabecera' => 'Felipe Carrillo Puerto'],
            ['nombre' => 'Ana Ellamin Pamplona Ramírez', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 13', 'cabecera' => 'Bacalar'],
            ['nombre' => 'Linda Saray Cobos Castro', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 14', 'cabecera' => 'Chetumal rural'],
            ['nombre' => 'Roberto Erales Jiménez', 'partido' => 'PT', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito 15', 'cabecera' => 'Chetumal urbano'],

            // REPRESENTACIÓN PROPORCIONAL (10)
            ['nombre' => 'Eduardo Lorenzo Martínez Arcila', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Cristina Torres Gómez', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Paula Pech Vázquez', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Luis Fernando Chávez Zepeda', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'José de la Peña Ruiz de Chávez', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Erick Gustavo Miranda García', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Iris Adriana Mora Vallejo', 'partido' => 'PRD', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'Judith Rodríguez Villanueva', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'José Luis Guillén López', 'partido' => 'MAS', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
            ['nombre' => 'José Luis Toledo Medina', 'partido' => 'MC', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => 'Plurinominal', 'cabecera' => 'Circunscripción Estatal'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getCronologiaHistorica(): array
    {
        return [
            ['numero' => 0, 'romano' => 'Constituyente', 'periodo' => '1974-1975', 'total_diputados' => 7, 'descripcion' => 'Congreso Constituyente del Estado de Quintana Roo tras decreto de estado libre y soberano'],
            ['numero' => 1, 'romano' => 'I', 'periodo' => '1975-1978', 'total_diputados' => 7, 'descripcion' => 'Primera Legislatura constitucional del Estado de Quintana Roo'],
            ['numero' => 2, 'romano' => 'II', 'periodo' => '1978-1981', 'total_diputados' => 9, 'descripcion' => 'Segunda Legislatura constitucional'],
            ['numero' => 3, 'romano' => 'III', 'periodo' => '1981-1984', 'total_diputados' => 11, 'descripcion' => 'Tercera Legislatura constitucional'],
            ['numero' => 4, 'romano' => 'IV', 'periodo' => '1984-1987', 'total_diputados' => 13, 'descripcion' => 'Cuarta Legislatura constitucional'],
            ['numero' => 5, 'romano' => 'V', 'periodo' => '1987-1990', 'total_diputados' => 15, 'descripcion' => 'Quinta Legislatura constitucional'],
            ['numero' => 6, 'romano' => 'VI', 'periodo' => '1990-1993', 'total_diputados' => 17, 'descripcion' => 'Sexta Legislatura constitucional'],
            ['numero' => 7, 'romano' => 'VII', 'periodo' => '1993-1996', 'total_diputados' => 19, 'descripcion' => 'Séptima Legislatura constitucional'],
            ['numero' => 8, 'romano' => 'VIII', 'periodo' => '1996-1999', 'total_diputados' => 21, 'descripcion' => 'Octava Legislatura constitucional'],
            ['numero' => 9, 'romano' => 'IX', 'periodo' => '1999-2002', 'total_diputados' => 25, 'descripcion' => 'Novena Legislatura constitucional (expansión a 25 curules)'],
            ['numero' => 10, 'romano' => 'X', 'periodo' => '2002-2005', 'total_diputados' => 25, 'descripcion' => 'Décima Legislatura constitucional'],
            ['numero' => 11, 'romano' => 'XI', 'periodo' => '2005-2008', 'total_diputados' => 25, 'descripcion' => 'Undécima Legislatura constitucional'],
            ['numero' => 12, 'romano' => 'XII', 'periodo' => '2008-2011', 'total_diputados' => 25, 'descripcion' => 'Duodécima Legislatura constitucional'],
            ['numero' => 13, 'romano' => 'XIII', 'periodo' => '2011-2013', 'total_diputados' => 25, 'descripcion' => 'Decimotercera Legislatura constitucional (ajuste de periodo)'],
            ['numero' => 14, 'romano' => 'XIV', 'periodo' => '2013-2016', 'total_diputados' => 25, 'descripcion' => 'Decimocuarta Legislatura constitucional'],
            ['numero' => 15, 'romano' => 'XV', 'periodo' => '2016-2019', 'total_diputados' => 25, 'descripcion' => 'Decimoquinta Legislatura constitucional'],
            ['numero' => 16, 'romano' => 'XVI', 'periodo' => '2019-2022', 'total_diputados' => 25, 'descripcion' => 'Decimosexta Legislatura constitucional'],
            ['numero' => 17, 'romano' => 'XVII', 'periodo' => '2022-2024', 'total_diputados' => 25, 'descripcion' => 'Decimoséptima Legislatura constitucional (periodo de homologación electoral de 2 años)'],
            ['numero' => 18, 'romano' => 'XVIII', 'periodo' => '2024-2027', 'total_diputados' => 25, 'descripcion' => 'Decimoctava Legislatura constitucional (en funciones)'],
        ];
    }
}
