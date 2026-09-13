<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\LandingSetting;
use App\Models\Plan;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Obtener toda la configuración pública de la Landing Page PWA.
     */
    public function index()
    {
        $settings = LandingSetting::all()->pluck('content', 'key')->toArray();

        $defaults = $this->getDefaultSettings();

        // Merge defaults with saved settings if key exists
        $merged = array_replace_recursive($defaults, $settings);

        // Cargar los planes activos directamente desde la base de datos
        $planes = Plan::where('activo', true)->orderBy('precio_mensual', 'asc')->get();
        if ($planes->isNotEmpty()) {
            $merged['planes'] = $planes;
        }

        return response()->json([
            'success' => true,
            'data' => $merged,
        ]);
    }

    /**
     * Actualizar la configuración de la Landing Page (Solo para usuario Master).
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array(strtolower($user->rol ?? ''), ['master', 'super admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para modificar la Landing Page.',
            ], 403);
        }

        $validated = $request->validate([
            'hero'           => 'nullable|array',
            'app_features'   => 'nullable|array',
            'extra_products' => 'nullable|array',
            'stats'          => 'nullable|array',
            'contact'        => 'nullable|array',
            'sections'       => 'nullable|array',
            'testimonials'   => 'nullable|array',
            'how_it_works'   => 'nullable|array',
            'trust_logos'    => 'nullable|array',
        ]);

        foreach ($validated as $key => $content) {
            if ($content !== null) {
                LandingSetting::updateOrCreate(
                    ['key' => $key],
                    ['content' => $content]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración de la Landing Page actualizada exitosamente.',
            'data' => $this->getDefaultSettings(),
        ]);
    }

    /**
     * Configuración por defecto sobria y profesional.
     */
    private function getDefaultSettings(): array
    {
        return [
            'hero' => [
                'title' => 'Sistema Inteligente de Gestión de Condominios AZPRO',
                'subtitle' => 'La plataforma SaaS de mayor innovación para la administración eficiente, transparente y automatizada de condominios, torres residenciales y complejos comerciales.',
                'badge' => 'Solución Tecnológica de Vanguardia ⚡',
                'primary_cta_text' => 'Conocer Características',
                'secondary_cta_text' => 'Iniciar Sesión',
                'banner_url' => '',
            ],
            'app_features' => [
                [
                    'icon' => 'mdi-currency-usd-circle-outline',
                    'title' => 'Facturación Multimoneda & Tasa BCV',
                    'description' => 'Cálculos automáticos en Bolívares y Dólares con sincronización en tiempo real de la tasa oficial BCV, congelamiento de tasa por mora e intereses.',
                ],
                [
                    'icon' => 'mdi-calculator-variant-outline',
                    'title' => 'Gestión de Alícuotas Dinámicas',
                    'description' => 'Distribución equitativa o por metros cuadrados en hasta 12 tipos de alícuotas personalizables por apartamento y torre.',
                ],
                [
                    'icon' => 'mdi-shield-account-outline',
                    'title' => 'Control de Visitantes & Acceso',
                    'description' => 'Registro digital ultrarrápido con códigos QR, captura de placas de vehículos, fotografías de cédula e historial de accesos en tiempo real.',
                ],
                [
                    'icon' => 'mdi-file-document-check-outline',
                    'title' => 'Recibos & Reportes PDF con QR',
                    'description' => 'Generación instantánea de avisos de cobro, comprobantes con QR de verificación pública de autenticidad y contabilidad transparente.',
                ],
                [
                    'icon' => 'mdi-whatsapp',
                    'title' => 'Notificaciones & Cartelera Digital',
                    'description' => 'Envío automático de recordatorios de pago por WhatsApp, comunicados oficiales por correo masivo y encuestas comunitarias.',
                ],
                [
                    'icon' => 'mdi-calendar-multiselect',
                    'title' => 'Reserva de Áreas Comunes',
                    'description' => 'Gestión inteligente para alquiler de salones, canchas y parilleras con validación automatizada de solvencia de propietarios.',
                ],
            ],
            'extra_products' => [
                [
                    'badge' => 'Desarrollo de Software',
                    'icon' => 'mdi-laptop-code',
                    'title' => 'Software a Medida & Portales Web',
                    'description' => 'Diseñamos y desarrollamos sistemas web empresariales, plataformas SaaS y aplicaciones móviles personalizadas a la medida de su organización.',
                    'cta_text' => 'Solicitar Cotización',
                    'link' => '#contacto',
                ],
                [
                    'badge' => 'Seguridad & IoT',
                    'icon' => 'mdi-door-sliding-lock',
                    'title' => 'Sistemas de Control de Acceso IoT',
                    'description' => 'Integración de portones automatizados, talanqueras inteligentes, tarjetas RFID, biometría y reconocimiento facial para condominios.',
                    'cta_text' => 'Más Información',
                    'link' => '#contacto',
                ],
                [
                    'badge' => 'Asesoría Profesional',
                    'icon' => 'mdi-scale-balance',
                    'title' => 'Consultoría Legal y Contable Condominial',
                    'description' => 'Asesoría especializada en leyes de propiedad horizontal, cobranza extrajudicial de morosidad y auditorías financieras independientes.',
                    'cta_text' => 'Consultar Expertos',
                    'link' => '#contacto',
                ],
                [
                    'badge' => 'Infraestructura',
                    'icon' => 'mdi-headset',
                    'title' => 'Soporte Técnico & Mantenimiento 24/7',
                    'description' => 'Planes de asistencia técnica integral para la infraestructura tecnológica, redes, cámaras CCTV e interconectividad de sus edificios.',
                    'cta_text' => 'Contratar Soporte',
                    'link' => '#contacto',
                ],
            ],
            'stats' => [
                'condominios' => '+50',
                'propietarios' => '+12,000',
                'recibos_procesados' => '+150,000',
                'satisfaccion' => '99.4%',
            ],
            'contact' => [
                'phone' => '+58 412-0000000',
                'whatsapp' => '+58 412-0000000',
                'email' => 'contacto@azpro.com',
                'address' => 'Caracas, Venezuela',
                'whatsapp_message' => 'Hola, me interesa conocer más sobre el Sistema Inteligente de Gestión de Condominios AZPRO',
            ],
            // Control de visibilidad de secciones desde el administrador
            'sections' => [
                'hero'            => true,   // Siempre visible (fijo)
                'trust_logos'     => false,  // Logos de confianza (marquee)
                'how_it_works'    => false,  // Cómo Funciona (3 pasos)
                'features'        => true,   // Características del sistema
                'testimonials'    => false,  // Testimonios de clientes
                'stats'           => true,   // Estadísticas de impacto
                'products'        => true,   // Otros productos/servicios
                'planes'          => true,   // Planes SaaS
                'whatsapp_float'  => true,   // Botón flotante WhatsApp (mobile)
            ],
            // Contenido de la sección Testimonios (vacío por defecto)
            'testimonials' => [
                [
                    'nombre'       => 'Lic. María Rodríguez',
                    'cargo'        => 'Administradora de Condominio',
                    'condominio'   => 'Torres El Pinar',
                    'plan'         => 'Profesional',
                    'cita'         => 'Con AZPRO automatizamos la cobranza y redujimos la morosidad en un 35%. El sistema es intuitivo y los residentes lo adoran.',
                    'calificacion' => 5,
                    'inicial'      => 'M',
                    'color'        => 'blue',
                ],
                [
                    'nombre'       => 'Ing. Carlos Méndez',
                    'cargo'        => 'Jefe de Administración',
                    'condominio'   => 'Residencias Los Aviadores',
                    'plan'         => 'Enterprise',
                    'cita'         => 'El módulo de control de visitantes con QR es espectacular. Eliminamos los registros manuales y ahorramos horas al mes.',
                    'calificacion' => 5,
                    'inicial'      => 'C',
                    'color'        => 'indigo',
                ],
                [
                    'nombre'       => 'Sr. José Pérez',
                    'cargo'        => 'Administrador Propietario',
                    'condominio'   => 'Edificio Parque Central',
                    'plan'         => 'Básico',
                    'cita'         => 'La tasa BCV automática nos ahorra horas de trabajo cada semana. Ya no hay errores de cálculo ni reclamos de propietarios.',
                    'calificacion' => 5,
                    'inicial'      => 'J',
                    'color'        => 'teal',
                ],
            ],
            // Contenido de la sección Cómo Funciona
            'how_it_works' => [
                [
                    'numero'       => '01',
                    'titulo'       => 'Registre su Condominio',
                    'descripcion'  => 'Configure su torre, apartamentos, propietarios y áreas comunes en minutos. Nuestro asistente de onboarding lo guía paso a paso.',
                    'icon'         => 'mdi-office-building-plus-outline',
                ],
                [
                    'numero'       => '02',
                    'titulo'       => 'Personalice y Configure',
                    'descripcion'  => 'Defina alícuotas, tipos de cobro, bancos receptores de pago, plantillas de recibos y configure las notificaciones automáticas por WhatsApp.',
                    'icon'         => 'mdi-tune-vertical',
                ],
                [
                    'numero'       => '03',
                    'titulo'       => 'Gestione sin Complicaciones',
                    'descripcion'  => 'Emita recibos PDF con QR, registre pagos, controle visitantes, gestione reservas y acceda a reportes financieros en tiempo real desde cualquier dispositivo.',
                    'icon'         => 'mdi-view-dashboard-outline',
                ],
            ],
            // Logos de confianza para el marquee
            'trust_logos' => [
                ['nombre' => 'Torres El Pinar'],
                ['nombre' => 'Res. Los Aviadores'],
                ['nombre' => 'Edificio Parque Central'],
                ['nombre' => 'Torres Las Mercedes'],
                ['nombre' => 'Res. La Castellana'],
                ['nombre' => 'Conjunto Los Chorros'],
                ['nombre' => 'Torres Caribe'],
                ['nombre' => 'Res. Altamira'],
            ],
        ];
    }
}
