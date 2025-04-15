/**
 * Script principal de la aplicación
 */
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const toggleSidebar = document.getElementById('toggle-sidebar');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleSidebar && sidebar) {
        toggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Theme Toggle
    const themeToggle = document.getElementById('theme-toggle');
    
    if (themeToggle) {
        // Check for dark mode preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
        
        // Listen for changes in color scheme preference
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
        
        // Theme toggle button
        themeToggle.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
        });
    }
    
    // Tabs
    const tabs = document.querySelectorAll('.tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Desactivar todas las pestañas
            this.parentElement.querySelectorAll('.tab').forEach(t => {
                t.classList.remove('active');
            });
            
            // Activar la pestaña actual
            this.classList.add('active');
            
            // Desactivar todos los contenidos
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(c => {
                c.classList.remove('active');
            });
            
            // Activar el contenido de la pestaña actual
            const activeContent = document.getElementById('tab-' + tabName);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });
    
    // Manejo de notificaciones
    const notifications = document.querySelectorAll('.notification');
    
    notifications.forEach(notification => {
        const closeButton = notification.querySelector('.notification-close');
        
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                notification.remove();
            });
        }
        
        // Auto cerrar después de 5 segundos
        setTimeout(function() {
            notification.style.opacity = '0';
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 5000);
    });
    
    // Manejo de elementos de factura
    const addItemButton = document.querySelector('.add-item-btn');
    
    if (addItemButton) {
        addItemButton.addEventListener('click', function() {
            const itemsContainer = document.getElementById('items-container');
            const itemRowTemplate = document.querySelector('.item-row-template');
            
            if (itemRowTemplate && itemsContainer) {
                const newRow = itemRowTemplate.cloneNode(true);
                newRow.classList.remove('item-row-template');
                newRow.style.display = '';
                
                // Limpiar valores
                newRow.querySelectorAll('input').forEach(input => {
                    input.value = '';
                });
                
                newRow.querySelector('select').selectedIndex = 0;
                
                // Añadir evento de eliminación
                const removeButton = newRow.querySelector('.item-remove');
                if (removeButton) {
                    removeButton.addEventListener('click', function() {
                        newRow.remove();
                        calculateTotals();
                    });
                }
                
                // Añadir eventos para calcular totales
                const quantityInput = newRow.querySelector('.item-quantity');
                const priceInput = newRow.querySelector('.item-price');
                const taxRateInput = newRow.querySelector('.item-tax-rate');
                
                if (quantityInput) {
                    quantityInput.addEventListener('input', calculateTotals);
                }
                
                if (priceInput) {
                    priceInput.addEventListener('input', calculateTotals);
                }
                
                if (taxRateInput) {
                    taxRateInput.addEventListener('input', calculateTotals);
                }
                
                // Añadir evento para selección de producto
                const productSelect = newRow.querySelector('.item-product');
                if (productSelect) {
                    productSelect.addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        if (selectedOption.value) {
                            const price = selectedOption.getAttribute('data-price');
                            const taxRate = selectedOption.getAttribute('data-tax-rate');
                            
                            if (priceInput && price) {
                                priceInput.value = price;
                            }
                            
                            if (taxRateInput && taxRate) {
                                taxRateInput.value = taxRate;
                            }
                            
                            calculateTotals();
                        }
                    });
                }
                
                itemsContainer.appendChild(newRow);
            }
        });
        
        // Inicializar cálculo de totales
        calculateTotals();
        
        // Añadir eventos a los elementos existentes
        document.querySelectorAll('.item-row:not(.item-row-template)').forEach(row => {
            const removeButton = row.querySelector('.item-remove');
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    row.remove();
                    calculateTotals();
                });
            }
            
            const quantityInput = row.querySelector('.item-quantity');
            const priceInput = row.querySelector('.item-price');
            const taxRateInput = row.querySelector('.item-tax-rate');
            
            if (quantityInput) {
                quantityInput.addEventListener('input', calculateTotals);
            }
            
            if (priceInput) {
                priceInput.addEventListener('input', calculateTotals);
            }
            
            if (taxRateInput) {
                taxRateInput.addEventListener('input', calculateTotals);
            }
            
            const productSelect = row.querySelector('.item-product');
            if (productSelect) {
                productSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value) {
                        const price = selectedOption.getAttribute('data-price');
                        const taxRate = selectedOption.getAttribute('data-tax-rate');
                        
                        if (priceInput && price) {
                            priceInput.value = price;
                        }
                        
                        if (taxRateInput && taxRate) {
                            taxRateInput.value = taxRate;
                        }
                        
                        calculateTotals();
                    }
                });
            }
        });
    }
});

/**
 * Calcula los totales de una factura
 */
function calculateTotals() {
    let subtotal = 0;
    let taxTotal = 0;
    let total = 0;
    
    document.querySelectorAll('.item-row:not(.item-row-template)').forEach(row => {
        const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const taxRate = parseFloat(row.querySelector('.item-tax-rate').value) || 0;
        
        const lineSubtotal = quantity * price;
        const lineTaxAmount = lineSubtotal * (taxRate / 100);
        const lineTotal = lineSubtotal + lineTaxAmount;
        
        // Actualizar campos de la línea
        const subtotalField = row.querySelector('.item-subtotal');
        const taxAmountField = row.querySelector('.item-tax-amount');
        const totalField = row.querySelector('.item-total');
        
        if (subtotalField) {
            subtotalField.value = lineSubtotal.toFixed(2);
        }
        
        if (taxAmountField) {
            taxAmountField.value = lineTaxAmount.toFixed(2);
        }
        
        if (totalField) {
            totalField.value = lineTotal.toFixed(2);
        }
        
        // Acumular totales
        subtotal += lineSubtotal;
        taxTotal += lineTaxAmount;
        total += lineTotal;
    });
    
    // Actualizar totales generales
    const subtotalElement = document.getElementById('invoice-subtotal');
    const taxTotalElement = document.getElementById('invoice-tax-total');
    const totalElement = document.getElementById('invoice-total');
    
    if (subtotalElement) {
        subtotalElement.textContent = subtotal.toFixed(2);
    }
    
    if (taxTotalElement) {
        taxTotalElement.textContent = taxTotal.toFixed(2);
    }
    
    if (totalElement) {
        totalElement.textContent = total.toFixed(2);
    }
}

/**
 * Confirmar eliminación
 */
function confirmDelete(url) {
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.action = url;
        
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.style.display = 'block';
        }
    }
}