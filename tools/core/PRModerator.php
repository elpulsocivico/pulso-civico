<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

require_once __DIR__ . '/GitHubOps.php';

class PRModerator
{
    private const REPO = 'elpulsocivico/pulso-civico';

    public static function run(): array
    {
        $actions = [
            104 => [
                'type' => 'approve_and_comment',
                'comment' => "¡Muchas gracias por la contribución @0xSixtos! Este fix es fundamental para que periodistas e investigadores puedan descargar datasets completos desde el explorador cívico. Aprobado para integración tras pasar el check de CI.",
                'merge_if_ready' => true,
            ],
            96 => [
                'type' => 'approve_and_comment',
                'comment' => "¡Excelente aporte @Kineyo! El modelado de grafo cívico aporta claridad visual y técnica sobre la interconexión entre dependencias, contratos y funcionarios públicos. Queda aprobado e integrado a la documentación.",
                'merge_if_ready' => false,
            ],
            95 => [
                'type' => 'approve_and_comment',
                'comment' => "Aprobado. Este cambio fortalece el carácter colaborativo y de ciencia ciudadana del proyecto, facilitando que voluntarios y periodistas puedan auditar fuentes en el pipeline de staging sin necesidad de programar.",
                'merge_if_ready' => false,
            ],
            94 => [
                'type' => 'approve_and_comment',
                'comment' => "Aprobado. El diagrama ASCII y la organización de esquemas en el diccionario de datos facilitan enormemente el onboarding de nuevos contribuidores al dataset. ¡Gracias!",
                'merge_if_ready' => false,
            ],
            97 => [
                'type' => 'comment_and_close',
                'comment' => "Hola @Kineyo. Agradecemos la iniciativa, pero cerraremos este PR. Pulso Cívico es una herramienta orientada al usuario final y a la sociedad civil; incorporar una ruta web pública (/desarrolladores) con prompts de IA añade peso, mantenimiento y no corresponde a la experiencia ciudadana. Este tipo de guías debe residir exclusivamente en el repositorio (docs/), no en la interfaz web.",
            ],
            98 => [
                'type' => 'comment_and_close',
                'comment' => "Hola @Kineyo, gracias por el tiempo invertido. Cerramos este PR debido a que AGENTS.md es un archivo de instrucciones de sistema y gobernanza técnica para asistentes de IA. Agregar sintaxis HTML compleja y prompts adicionales satura el contexto de tokens y afecta la precisión de los modelos. Debemos mantenerlo lo más conciso, estricto y en texto plano posible.",
            ],
            100 => [
                'type' => 'comment_and_close',
                'comment' => "Hola @Kineyo. De igual forma que con el PR #98, GEMINI.md es un archivo crítico de configuración operativa y optimización de tokens para el entorno de trabajo. No se admiten prompts externos ni elementos HTML en este archivo para preservar su eficiencia y legibilidad por el modelo. Cerramos el PR.",
            ],
            93 => [
                'type' => 'comment_and_close',
                'comment' => "Cerramos este PR por encontrarse incompleto y quedar cubierto por las propuestas más recientes de documentación en el PR #102. Gracias.",
            ],
            99 => [
                'type' => 'comment_and_close',
                'comment' => "Cerramos este PR para consolidar los cambios en una sola propuesta unificada junto con la documentación de contribución general una vez que las tareas estén al 100%. Por favor no abras PRs parciales por archivo.",
            ],
            101 => [
                'type' => 'comment_and_close',
                'comment' => "Cerramos este PR para evitar fragmentación del historial. La guía de scrapers debe centrarse en la extracción de datos y el respeto a servidores públicos; no requiere sublistas de agentes. Si deseas pulir la guía de instalación por OS, por favor inclúyelo en un único PR consolidado de docs.",
            ],
            102 => [
                'type' => 'comment_only',
                'comment' => "Hola @Kineyo. Para mantener un historial de commits limpio y ordenado, no podemos aceptar PRs atómicos con tareas pendientes (7/12). Por favor consolida los comandos por OS de README y CONTRIBUTING en un único PR completo, omitiendo la sección de prompts de agentes para no sobrecargar el README general.",
            ],
        ];

        $results = [];

        foreach ($actions as $prNum => $plan) {
            $entry = [
                'pr' => $prNum,
                'action_type' => $plan['type'],
                'comment_posted' => false,
                'review_approved' => false,
                'closed' => false,
                'merged' => false,
                'errors' => [],
            ];

            // 1. Publicar comentario
            $commentRes = GitHubOps::api('POST', "repos/" . self::REPO . "/issues/{$prNum}/comments", [
                'body' => $plan['comment'],
            ]);
            $entry['comment_posted'] = $commentRes['ok'];
            if (!$commentRes['ok']) {
                $entry['errors'][] = 'Error comentario: ' . ($commentRes['data']['message'] ?? $commentRes['error'] ?? 'desconocido');
            }

            // 2. Acciones específicas por tipo
            if ($plan['type'] === 'approve_and_comment') {
                $reviewRes = GitHubOps::api('POST', "repos/" . self::REPO . "/pulls/{$prNum}/reviews", [
                    'event' => 'APPROVE',
                    'body' => 'Approved: ' . $plan['comment'],
                ]);
                $entry['review_approved'] = $reviewRes['ok'];
                if (!$reviewRes['ok']) {
                    $entry['errors'][] = 'Error review: ' . ($reviewRes['data']['message'] ?? $reviewRes['error'] ?? 'desconocido');
                }

                if (!empty($plan['merge_if_ready'])) {
                    // Si se puede mergear
                    $mergeRes = GitHubOps::mergePullRequest($prNum, 'Merge PR #' . $prNum . ' - fix exportar CSV', 'merge', self::REPO);
                    $entry['merged'] = $mergeRes['ok'];
                    if (!$mergeRes['ok']) {
                        $entry['errors'][] = 'Merge info: ' . ($mergeRes['data']['message'] ?? 'Pendiente de checks o conflicto');
                    }
                }
            } elseif ($plan['type'] === 'comment_and_close') {
                $closeRes = GitHubOps::api('PATCH', "repos/" . self::REPO . "/pulls/{$prNum}", [
                    'state' => 'closed',
                ]);
                $entry['closed'] = $closeRes['ok'];
                if (!$closeRes['ok']) {
                    $entry['errors'][] = 'Error cierre: ' . ($closeRes['data']['message'] ?? $closeRes['error'] ?? 'desconocido');
                }
            }

            $results[$prNum] = $entry;
        }

        return $results;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $out = PRModerator::run();
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
