</div><!-- .main-content -->
    </div><!-- .container -->
    
    <!-- Modal para confirmación de eliminación -->
    <div class="modal" id="deleteModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmar eliminación</h2>
                <span class="close" id="closeModal">&times;</span>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelDelete">Cancelar</button>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Función para la confirmación de eliminación
        function confirmDelete(url) {
            const deleteForm = document.getElementById('deleteForm');
            deleteForm.action = url;
            
            const deleteModal = document.getElementById('deleteModal');
            deleteModal.style.display = 'block';
            
            document.getElementById('closeModal').onclick = function() {
                deleteModal.style.display = 'none';
            };
            
            document.getElementById('cancelDelete').onclick = function() {
                deleteModal.style.display = 'none';
            };
            
            window.onclick = function(event) {
                if (event.target == deleteModal) {
                    deleteModal.style.display = 'none';
                }
            };
        }
    </script>
</body>
</html>