<?php
/**
 * Funciones auxiliares para vistas
 */

if (!function_exists('generateSelectOptions')) {
    /**
     * Generar una lista de opciones para un select
     */
    function generateSelectOptions($options, $selectedValue = null) {
        $html = '';
        
        foreach ($options as $value => $label) {
            $selected = $selectedValue !== null && $selectedValue == $value ? 'selected' : '';
            $html .= "<option value=\"" . e($value) . "\" $selected>" . e($label) . "</option>";
        }
        
        return $html;
    }
}

if (!function_exists('selected')) {
    /**
     * Generar atributo selected para select
     */
    function selected($value, $current) {
        return $value == $current ? 'selected' : '';
    }
}

if (!function_exists('checked')) {
    /**
     * Generar atributo checked para inputs
     */
    function checked($value, $current) {
        return $value == $current ? 'checked' : '';
    }
}

if (!function_exists('old')) {
    /**
     * Obtener el valor de un campo del formulario anterior (en caso de error)
     */
    function old($field, $default = '') {
        return $_SESSION['form_data'][$field] ?? $default;
    }
}

if (!function_exists('formError')) {
    /**
     * Mostrar un mensaje de error para un campo de formulario
     */
    function formError($field) {
        if (isset($_SESSION['form_errors'][$field])) {
            return '<div class="invalid-feedback">' . $_SESSION['form_errors'][$field] . '</div>';
        }
        
        return '';
    }
}

if (!function_exists('hasError')) {
    /**
     * Verificar si un campo tiene error
     */
    function hasError($field) {
        return isset($_SESSION['form_errors'][$field]) ? 'is-invalid' : '';
    }
}

if (!function_exists('truncate')) {
    /**
     * Truncar texto a un máximo de caracteres
     */
    function truncate($text, $length = 100, $append = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        
        return $text . $append;
    }
}

// Nota: No incluimos statusBadge() aquí, ya que está definida en functions.php