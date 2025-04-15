<?php
$isEditing = isset($client);
$pageTitle = $isEditing ? 'Editar Cliente' : 'Nuevo Cliente';

$breadcrumbs = [
    'Clientes' => url('/index.php?page=clients'),
    $isEditing ? 'Editar' : 'Nuevo' => null
];

// Obtener datos de formulario en caso de error
$formData = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['form_errors'] ?? [];

// Limpiar datos de sesión
unset($_SESSION['form_data'], $_SESSION['form_errors']);

include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <form id="client-form" method="POST" action="<?php echo url('/index.php?page=clients&action=' . ($isEditing ? 'update&id=' . $client['id'] : 'store')); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Nombre/Empresa *</label>
                    <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" name="name" required value="<?php echo e($formData['name'] ?? ($isEditing ? $client['name'] : '')); ?>">
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" name="email" required value="<?php echo e($formData['email'] ?? ($isEditing ? $client['email'] : '')); ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" name="phone" value="<?php echo e($formData['phone'] ?? ($isEditing ? $client['phone'] : '')); ?>">
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">NIF/CIF *</label>
                    <input type="text" class="form-control <?php echo isset($errors['tax_number']) ? 'is-invalid' : ''; ?>" name="tax_number" required value="<?php echo e($formData['tax_number'] ?? ($isEditing ? $client['tax_number'] : '')); ?>">
                    <?php if (isset($errors['tax_number'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['tax_number']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Dirección</label>
            <textarea class="form-control" name="address" rows="3"><?php echo e($formData['address'] ?? ($isEditing ? $client['address'] : '')); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Límite de crédito</label>
                    <input type="number" step="0.01" class="form-control" name="credit_limit" value="<?php echo e($formData['credit_limit'] ?? ($isEditing ? $client['credit_limit'] : '0.00')); ?>">
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Moneda</label>
                    <select class="form-control" name="currency">
                        <option value="EUR" <?php echo (isset($formData['currency']) ? $formData['currency'] == 'EUR' : ($isEditing ? $client['currency'] == 'EUR' : true)) ? 'selected' : ''; ?>>Euro (€)</option>
                        <option value="USD" <?php echo (isset($formData['currency']) ? $formData['currency'] == 'USD' : ($isEditing ? $client['currency'] == 'USD' : false)) ? 'selected' : ''; ?>>Dólar estadounidense ($)</option>
                        <option value="GBP" <?php echo (isset($formData['currency']) ? $formData['currency'] == 'GBP' : ($isEditing ? $client['currency'] == 'GBP' : false)) ? 'selected' : ''; ?>>Libra esterlina (£)</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Notas</label>
            <textarea class="form-control" name="notes" rows="3"><?php echo e($formData['notes'] ?? ($isEditing ? $client['notes'] : '')); ?></textarea>
        </div>
        
        <!-- Campos personalizados -->
        <?php if (!empty($customFields)): ?>
            <div class="custom-fields-section">
                <h3 style="margin-bottom: 15px;">Campos personalizados</h3>
                
                <?php foreach ($customFields as $field): ?>
                    <?php
                    $fieldName = 'custom_' . $field['id'];
                    $fieldValue = $formData[$fieldName] ?? ($isEditing && isset($customFieldValues[$field['id']]) ? $customFieldValues[$field['id']] : '');
                    ?>
                    
                    <div class="custom-field">
                        <label class="custom-field-label"><?php echo e($field['name']); ?><?php echo $field['required'] ? ' *' : ''; ?></label>
                        
                        <?php if ($field['type'] == 'text'): ?>
                            <input type="text" class="form-control <?php echo isset($errors[$fieldName]) ? 'is-invalid' : ''; ?>" name="<?php echo $fieldName; ?>" <?php echo $field['required'] ? 'required' : ''; ?> value="<?php echo e($fieldValue); ?>">
                        <?php elseif ($field['type'] == 'number'): ?>
                            <input type="number" class="form-control <?php echo isset($errors[$fieldName]) ? 'is-invalid' : ''; ?>" name="<?php echo $fieldName; ?>" <?php echo $field['required'] ? 'required' : ''; ?> value="<?php echo e($fieldValue); ?>">
                        <?php elseif ($field['type'] == 'date'): ?>
                            <input type="date" class="form-control <?php echo isset($errors[$fieldName]) ? 'is-invalid' : ''; ?>" name="<?php echo $fieldName; ?>" <?php echo $field['required'] ? 'required' : ''; ?> value="<?php echo e($fieldValue); ?>">
                        <?php elseif ($field['type'] == 'select'): ?>
                            <select class="form-control <?php echo isset($errors[$fieldName]) ? 'is-invalid' : ''; ?>" name="<?php echo $fieldName; ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                                <option value="">Seleccionar...</option>
                                <?php foreach (explode(',', $field['options']) as $option): ?>
                                    <option value="<?php echo e(trim($option)); ?>" <?php echo $fieldValue == trim($option) ? 'selected' : ''; ?>><?php echo e(trim($option)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['type'] == 'textarea'): ?>
                            <textarea class="form-control <?php echo isset($errors[$fieldName]) ? 'is-invalid' : ''; ?>" name="<?php echo $fieldName; ?>" rows="3" <?php echo $field['required'] ? 'required' : ''; ?>><?php echo e($fieldValue); ?></textarea>
                        <?php endif; ?>
                        
                        <?php if (isset($errors[$fieldName])): ?>
                            <div class="invalid-feedback"><?php echo $errors[$fieldName]; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: right; margin-top: 20px;">
            <a href="<?php echo url('/index.php?page=clients'); ?>" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar Cliente</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>