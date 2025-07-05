const { ethers } = require("hardhat");
const { formatEther } = require("ethers");

async function main() {
  const blockNumber = 1; // Change this to inspect other blocks
  const block = await ethers.provider.getBlock(blockNumber);
  if (!block) {
    console.log(`Block ${blockNumber} not found.`);
    return;
  }
  if (!block.transactions.length) {
    console.log(`Block ${blockNumber} has no transactions.`);
    return;
  }
  console.log(`Block ${blockNumber} transactions:`);
  for (const txHash of block.transactions) {
    const tx = await ethers.provider.getTransaction(txHash);
    if (!tx) continue;
    console.log(`- Hash: ${tx.hash}`);
    console.log(`  From: ${tx.from}`);
    console.log(`  To:   ${tx.to}`); // Note: 'to' is null for contract creation
    console.log(`  Value: ${formatEther(tx.value)} ETH`);
    console.log("---");
  }
}

main().catch(console.error); 