<?php
$isEditing = isset($invoice);
$pageTitle = $isEditing ? 'Editar Factura' : 'Nueva Factura';

include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <h2><?php echo $pageTitle; ?></h2>
    
    <form method="post" action="<?php echo url('/index.php?page=invoices&action=' . ($isEditing ? 'update&id=' . $invoice['id'] : 'store')); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="invoice_number">Número de factura <span class="required">*</span></label>
                <input type="text" id="invoice_number" name="invoice_number" class="form-control <?php echo hasError('invoice_number'); ?>" 
                       value="<?php echo $isEditing ? e($invoice['invoice_number']) : e($nextInvoiceNumber); ?>" 
                       required>
                <?php echo formError('invoice_number'); ?>
            </div>
            
            <div class="form-group">
                <label for="client_id">Cliente <span class="required">*</span></label>
                <select id="client_id" name="client_id" class="form-control <?php echo hasError('client_id'); ?>" required>
                    <option value="">Seleccione un cliente</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $isEditing && $invoice['client_id'] == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo e($client['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo formError('client_id'); ?>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="invoice_date">Fecha de emisión <span class="required">*</span></label>
                <input type="date" id="invoice_date" name="invoice_date" class="form-control <?php echo hasError('invoice_date'); ?>" 
                       value="<?php echo $isEditing ? e($invoice['invoice_date']) : date('Y-m-d'); ?>" 
                       required>
                <?php echo formError('invoice_date'); ?>
            </div>
            
            <div class="form-group">
                <label for="due_date">Fecha de vencimiento <span class="required">*</span></label>
                <input type="date" id="due_date" name="due_date" class="form-control <?php echo hasError('due_date'); ?>" 
                       value="<?php echo $isEditing ? e($invoice['due_date']) : date('Y-m-d', strtotime('+30 days')); ?>" 
                       required>
                <?php echo formError('due_date'); ?>
            </div>
        </div>
        
        <div class="form-group">
            <label for="status">Estado</label>
            <select id="status" name="status" class="form-control">
                <option value="draft" <?php echo $isEditing && $invoice['status'] == 'draft' ? 'selected' : ''; ?>>Borrador</option>
                <option value="sent" <?php echo $isEditing && $invoice['status'] == 'sent' ? 'selected' : ''; ?>>Enviada</option>
                <option value="paid" <?php echo $isEditing && $invoice['status'] == 'paid' ? 'selected' : ''; ?>>Pagada</option>
                <option value="overdue" <?php echo $isEditing && $invoice['status'] == 'overdue' ? 'selected' : ''; ?>>Vencida</option>
                <option value="cancelled" <?php echo $isEditing && $invoice['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelada</option>
            </select>
        </div>
        
        <!-- Elementos de la factura -->
        <h3>Artículos</h3>
        <?php if (isset($errors['items'])): ?>
            <div class="alert alert-danger"><?php echo $errors['items']; ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <table id="invoice-items">
                <thead>
                    <tr>
                        <th width="30%">Producto</th>
                        <th width="30%">Descripción</th>
                        <th width="10%">Cantidad</th>
                        <th width="10%">Precio</th>
                        <th width="10%">% IVA</th>
                        <th width="10%">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($isEditing && isset($invoiceItems) && !empty($invoiceItems)): ?>
                        <?php foreach ($invoiceItems as $index => $item): ?>
                            <tr class="item-row">
                                <td>
                                    <select name="product[]" class="form-control product-select">
                                        <option value="">Seleccione un producto</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                    data-price="<?php echo $product['sale_price']; ?>" 
                                                    data-tax="<?php echo $product['tax_rate']; ?>"
                                                    <?php echo $item['product_id'] == $product['id'] ? 'selected' : ''; ?>>
                                                <?php echo e($product['name']); ?> (<?php echo e($product['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="description[]" class="form-control" value="<?php echo e($item['description']); ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="quantity[]" class="form-control quantity" min="1" step="1" value="<?php echo e($item['quantity']); ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="price[]" class="form-control price" min="0" step="0.01" value="<?php echo e($item['price']); ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="tax_rate[]" class="form-control tax-rate" min="0" step="0.01" value="<?php echo e($item['tax_rate']); ?>">
                                    <input type="hidden" name="tax_amount[]" class="tax-amount" value="<?php echo e($item['tax_amount']); ?>">
                                </td>
                                <td>
                                    <input type="number" name="item_total[]" class="form-control item-total" value="<?php echo e($item['total']); ?>" readonly>
                                </td>
                                <td>
                                    <button type="button" class="btn-icon remove-row">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="item-row">
                            <td>
                                <select name="product[]" class="form-control product-select">
                                    <option value="">Seleccione un producto</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" 
                                                data-price="<?php echo $product['sale_price']; ?>" 
                                                data-tax="<?php echo $product['tax_rate']; ?>">
                                            <?php echo e($product['name']); ?> (<?php echo e($product['code']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="description[]" class="form-control" required>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" class="form-control quantity" min="1" step="1" value="1" required>
                            </td>
                            <td>
                                <input type="number" name="price[]" class="form-control price" min="0" step="0.01" value="0.00" required>
                            </td>
                            <td>
                                <input type="number" name="tax_rate[]" class="form-control tax-rate" min="0" step="0.01" value="<?php echo $defaultTaxRate ?? 21.00; ?>">
                                <input type="hidden" name="tax_amount[]" class="tax-amount" value="0.00">
                            </td>
                            <td>
                                <input type="number" name="item_total[]" class="form-control item-total" value="0.00" readonly>
                            </td>
                            <td>
                                <button type="button" class="btn-icon remove-row">🗑️</button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7">
                            <button type="button" class="btn btn-outline" id="add-row">+ Agregar artículo</button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="invoice-totals">
            <div class="form-row">
                <div class="form-group">
                    <label for="subtotal">Subtotal</label>
                    <input type="number" id="subtotal" name="subtotal" class="form-control" 
                           value="<?php echo $isEditing ? e($invoice['subtotal']) : '0.00'; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="tax_total">Total IVA</label>
                    <input type="number" id="tax_total" name="tax_total" class="form-control" 
                           value="<?php echo $isEditing ? e($invoice['tax_total']) : '0.00'; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="total">Total</label>
                    <input type="number" id="total" name="total" class="form-control" 
                           value="<?php echo $isEditing ? e($invoice['total']) : '0.00'; ?>" readonly>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="notes">Notas</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo $isEditing ? e($invoice['notes']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="footer">Pie de factura</label>
            <textarea id="footer" name="footer" class="form-control" rows="2"><?php echo $isEditing ? e($invoice['footer']) : 'Gracias por su confianza.'; ?></textarea>
        </div>
        
        <!-- Campos personalizados -->
        <?php if (!empty($customFields)): ?>
            <h3>Campos personalizados</h3>
            <?php foreach ($customFields as $field): ?>
                <div class="form-group">
                    <label for="custom_<?php echo $field['id']; ?>">
                        <?php echo e($field['name']); ?>
                        <?php if ($field['required']): ?><span class="required">*</span><?php endif; ?>
                    </label>
                    
                    <?php 
                    $fieldValue = '';
                    if ($isEditing && isset($customFieldValues[$field['id']])) {
                        $fieldValue = $customFieldValues[$field['id']];
                    }
                    ?>
                    
                    <?php if ($field['type'] === 'text'): ?>
                        <input type="text" 
                               id="custom_<?php echo $field['id']; ?>" 
                               name="custom_fields[<?php echo $field['id']; ?>]" 
                               class="form-control"
                               value="<?php echo e($fieldValue); ?>"
                               <?php echo $field['required'] ? 'required' : ''; ?>>
                    <?php elseif ($field['type'] === 'number'): ?>
                        <input type="number" 
                               id="custom_<?php echo $field['id']; ?>" 
                               name="custom_fields[<?php echo $field['id']; ?>]" 
                               class="form-control"
                               value="<?php echo e($fieldValue); ?>"
                               <?php echo $field['required'] ? 'required' : ''; ?>>
                    <?php elseif ($field['type'] === 'date'): ?>
                        <input type="date" 
                               id="custom_<?php echo $field['id']; ?>" 
                               name="custom_fields[<?php echo $field['id']; ?>]" 
                               class="form-control"
                               value="<?php echo e($fieldValue); ?>"
                               <?php echo $field['required'] ? 'required' : ''; ?>>
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea id="custom_<?php echo $field['id']; ?>" 
                                  name="custom_fields[<?php echo $field['id']; ?>]" 
                                  class="form-control" 
                                  rows="3"
                                  <?php echo $field['required'] ? 'required' : ''; ?>><?php echo e($fieldValue); ?></textarea>
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select id="custom_<?php echo $field['id']; ?>" 
                                name="custom_fields[<?php echo $field['id']; ?>]" 
                                class="form-control"
                                <?php echo $field['required'] ? 'required' : ''; ?>>
                            <option value="">Seleccione una opción</option>
                            <?php 
                            $options = explode(',', $field['options']);
                            foreach ($options as $option): 
                                $option = trim($option);
                            ?>
                                <option value="<?php echo e($option); ?>" <?php echo $fieldValue === $option ? 'selected' : ''; ?>>
                                    <?php echo e($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?php echo url('/index.php?page=invoices'); ?>" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calcular totales iniciales
        calculateTotals();
        
        // Agregar nueva fila
        document.getElementById('add-row').addEventListener('click', function() {
            var tbody = document.querySelector('#invoice-items tbody');
            var template = document.querySelector('.item-row').cloneNode(true);
            
            // Limpiar valores en la nueva fila
            template.querySelectorAll('input[type="text"], input[type="number"]').forEach(function(input) {
                input.value = input.type === 'number' ? '0' : '';
            });
            
            template.querySelector('.quantity').value = '1';
            template.querySelector('.tax-rate').value = '<?php echo $defaultTaxRate ?? 21.00; ?>';
            template.querySelector('select').selectedIndex = 0;
            
            // Asignar eventos a la nueva fila
            attachRowEvents(template);
            
            tbody.appendChild(template);
        });
        
        // Asignar eventos a filas existentes
        document.querySelectorAll('.item-row').forEach(function(row) {
            attachRowEvents(row);
        });
        
        function attachRowEvents(row) {
            // Eliminar fila
            row.querySelector('.remove-row').addEventListener('click', function() {
                if (document.querySelectorAll('.item-row').length > 1) {
                    row.remove();
                    calculateTotals();
                } else {
                    alert('Debe haber al menos un artículo');
                }
            });
            
            // Evento de cambio de producto
            row.querySelector('.product-select').addEventListener('change', function() {
                var option = this.options[this.selectedIndex];
                if (option.value) {
                    row.querySelector('.price').value = option.dataset.price;
                    row.querySelector('.tax-rate').value = option.dataset.tax;
                }
                calculateRowTotal(row);
            });
            
            // Eventos de cambio de cantidad o precio
            row.querySelector('.quantity').addEventListener('input', function() {
                calculateRowTotal(row);
            });
            
            row.querySelector('.price').addEventListener('input', function() {
                calculateRowTotal(row);
            });
            
            row.querySelector('.tax-rate').addEventListener('input', function() {
                calculateRowTotal(row);
            });
        }
        
        function calculateRowTotal(row) {
            var quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            var price = parseFloat(row.querySelector('.price').value) || 0;
            var taxRate = parseFloat(row.querySelector('.tax-rate').value) || 0;
            
            var subtotal = quantity * price;
            var taxAmount = subtotal * (taxRate / 100);
            var total = subtotal + taxAmount;
            
            row.querySelector('.tax-amount').value = taxAmount.toFixed(2);
            row.querySelector('.item-total').value = total.toFixed(2);
            
            calculateTotals();
        }
        
        function calculateTotals() {
            var subtotal = 0;
            var taxTotal = 0;
            var total = 0;
            
            document.querySelectorAll('.item-row').forEach(function(row) {
                var quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                var price = parseFloat(row.querySelector('.price').value) || 0;
                var taxAmount = parseFloat(row.querySelector('.tax-amount').value) || 0;
                
                subtotal += quantity * price;
                taxTotal += taxAmount;
            });
            
            total = subtotal + taxTotal;
            
            document.getElementById('subtotal').value = subtotal.toFixed(2);
            document.getElementById('tax_total').value = taxTotal.toFixed(2);
            document.getElementById('total').value = total.toFixed(2);
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>