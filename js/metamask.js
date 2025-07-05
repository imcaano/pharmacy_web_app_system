// MetaMask Integration Utility
// Usage: connectWallet(), sendPayment(to, amountInEth)

async function connectWallet() {
    if (typeof window.ethereum !== 'undefined') {
        try {
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            return accounts[0];
        } catch (error) {
            alert('MetaMask connection failed: ' + error.message);
            return null;
        }
    } else {
        alert('MetaMask is not installed.');
        return null;
    }
}

async function sendPayment(to, amountInEth) {
    if (typeof window.ethereum === 'undefined') {
        alert('MetaMask is not installed.');
        return { error: 'MetaMask not installed' };
    }
    try {
        const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
        const from = accounts[0];
        const txParams = {
            from: from,
            to: to,
            value: window.ethereum.utils ? window.ethereum.utils.toWei(amountInEth, 'ether') : (parseInt(amountInEth * 1e18)).toString(16),
        };
        // Send transaction
        const txHash = await window.ethereum.request({
            method: 'eth_sendTransaction',
            params: [txParams],
        });
        // Fee is not available until mined, so show 0.00 for now
        return { txHash: txHash, fee: '0.00', status: 'Pending' };
    } catch (error) {
        return { error: error.message };
    }
}

// Export for inline script usage
window.connectWallet = connectWallet;
window.sendPayment = sendPayment; 