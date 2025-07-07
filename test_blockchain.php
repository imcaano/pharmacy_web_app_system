<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain Test</title>
    <script src="https://cdn.jsdelivr.net/npm/ethers@5.7.2/dist/ethers.umd.min.js"></script>
    <script src="assets/js/blockchain.js"></script>
</head>
<body>
    <h1>Blockchain Connection Test</h1>
    
    <button onclick="testConnection()">Test MetaMask Connection</button>
    <button onclick="testContract()">Test Contract Connection</button>
    <button onclick="testRegistration()">Test User Registration</button>
    
    <div id="results"></div>
    
    <script>
        async function testConnection() {
            try {
                const { provider, signer } = await connectMetaMask();
                const address = await signer.getAddress();
                document.getElementById('results').innerHTML += `<p>✅ MetaMask connected! Address: ${address}</p>`;
            } catch (error) {
                document.getElementById('results').innerHTML += `<p>❌ MetaMask error: ${error.message}</p>`;
            }
        }
        
        async function testContract() {
            try {
                const { signer } = await connectMetaMask();
                const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
                document.getElementById('results').innerHTML += `<p>✅ Contract connected! Address: ${CONTRACT_ADDRESS}</p>`;
            } catch (error) {
                document.getElementById('results').innerHTML += `<p>❌ Contract error: ${error.message}</p>`;
            }
        }
        
        async function testRegistration() {
            try {
                const testEmail = prompt('Enter email to register as test user:', 'test@example.com');
                if (!testEmail) {
                    document.getElementById('results').innerHTML += `<p>❌ Registration cancelled.</p>`;
                    return;
                }
                const result = await registerUserOnChain(testEmail);
                document.getElementById('results').innerHTML += `<p>✅ Registration successful! Email: ${testEmail}<br>Hash: ${result.userHash}<br>TxHash: ${result.txHash}</p>`;
            } catch (error) {
                document.getElementById('results').innerHTML += `<p>❌ Registration error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html> 