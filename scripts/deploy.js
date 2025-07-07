const { ethers } = require("hardhat");
const fs = require('fs');
const path = require('path');

async function main() {
  const PharmaRegistry = await ethers.getContractFactory("PharmaRegistry");
  const contract = await PharmaRegistry.deploy();
  await contract.waitForDeployment();
  console.log("PharmaRegistry deployed to:", contract.target);
  
  // Save the contract address to a file
  fs.writeFileSync('deployed_address.txt', contract.target);
  
  // Update the contract address in blockchain.js
  try {
    const blockchainJsPath = path.join(__dirname, '../assets/js/blockchain.js');
    let blockchainJsContent = fs.readFileSync(blockchainJsPath, 'utf8');
    
    // Replace the placeholder with the actual address
    blockchainJsContent = blockchainJsContent.replace(
      /const CONTRACT_ADDRESS = 'PASTE_YOUR_DEPLOYED_CONTRACT_ADDRESS_HERE';/,
      `const CONTRACT_ADDRESS = '${contract.target}';`
    );
    
    // Write back to the file
    fs.writeFileSync(blockchainJsPath, blockchainJsContent);
    
    console.log('✅ Contract address automatically updated in assets/js/blockchain.js');
    console.log('🎉 Your pharmacy system is ready to use with blockchain integration!');
    
  } catch (error) {
    console.log('⚠️  Could not automatically update blockchain.js. Please manually update the contract address.');
    console.log('   Contract address:', contract.target);
  }
}

main().catch(console.error); 