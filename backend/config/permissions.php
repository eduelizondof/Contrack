<?php

/**
 * Configuración centralizada de permisos del sistema
 * 
 * Estructura: módulo => acción => recursos
 * Formato de permiso generado: modulo.accion.recurso
 * 
 * Ejemplo: 'clientes.crear.contacto'
 */

return [
    'configuracion' => [
        'ver' => ['usuario', 'rol', 'permiso'],
        'crear' => ['usuario', 'rol', 'permiso'],
        'editar' => ['usuario', 'rol', 'permiso'],
        'eliminar' => ['usuario', 'rol', 'permiso'],
    ],

    'chat' => [
        'ver' => ['conversacion', 'mensaje'],
        'crear' => ['conversacion', 'mensaje'],
        'editar' => ['conversacion', 'mensaje'],
        'eliminar' => ['conversacion', 'mensaje'],
    ],

    'notificaciones' => [
        'ver' => ['notificacion'],
        'crear' => ['notificacion'],
        'editar' => ['notificacion'],
        'eliminar' => ['notificacion'],
    ],
];
