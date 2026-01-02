</main>

    <div id="scanner-modal">
        <h3 style="color: #fff; margin-bottom: 15px;">Escaneando Código...</h3>
        <div id="reader"></div>
        <button id="close-scanner" onclick="stopScanner()">CANCELAR CÁMARA</button>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        let html5QrcodeScanner;

        // Función para abrir la cámara
        function startScanner(inputId) {
            document.getElementById('scanner-modal').style.display = 'flex';
            
            html5QrcodeScanner = new Html5Qrcode("reader");
            
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            // Preferir cámara trasera
            html5QrcodeScanner.start({ facingMode: "environment" }, config, (decodedText, decodedResult) => {
                // CUANDO ENCUENTRA EL CÓDIGO:
                
                // 1. Poner el valor en el input
                document.getElementById(inputId).value = decodedText;
                
                // 2. Reproducir sonido "Beep"
                let audio = new Audio('../../assets/beep.mp3'); // Opcional si tienes el archivo
                // audio.play().catch(e => {}); 

                // 3. Cerrar escáner
                stopScanner();

                // 4. Enfocar el campo para que se vea
                document.getElementById(inputId).focus();
                
            }).catch(err => {
                // Errores silenciosos de inicio de cámara
            });
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    document.getElementById('scanner-modal').style.display = 'none';
                    html5QrcodeScanner.clear();
                }).catch(err => {
                    document.getElementById('scanner-modal').style.display = 'none';
                });
            } else {
                document.getElementById('scanner-modal').style.display = 'none';
            }
        }
    </script>
</body>
</html>