const fs = require('fs');
const path = require('path');

async function main() {
    try {
        // Read the deployed contract address
        const deployedAddress = fs.readFileSync('deployed_address.txt', 'utf8').trim();
        console.log('Deployed contract address:', deployedAddress);
        
        // Read the blockchain.js file
        const blockchainJsPath = path.join(__dirname, '../assets/js/blockchain.js');
        let blockchainJsContent = fs.readFileSync(blockchainJsPath, 'utf8');
        
        // Replace the placeholder with the actual address
        blockchainJsContent = blockchainJsContent.replace(
            /const CONTRACT_ADDRESS = 'PASTE_YOUR_DEPLOYED_CONTRACT_ADDRESS_HERE';/,
            `const CONTRACT_ADDRESS = '${deployedAddress}';`
        );
        
        // Write back to the file
        fs.writeFileSync(blockchainJsPath, blockchainJsContent);
        
        console.log('✅ Contract address updated in assets/js/blockchain.js');
        console.log('📝 You can now use the blockchain features in your web app!');
        
    } catch (error) {
        console.error('❌ Error updating contract address:', error.message);
        console.log('💡 Make sure you have deployed the contract first:');
        console.log('   npx hardhat run scripts/deploy.js --network localhost');
    }
}

main().catch(console.error); 