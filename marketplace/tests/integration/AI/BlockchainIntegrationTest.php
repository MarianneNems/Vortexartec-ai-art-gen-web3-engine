<?php
namespace Vortex\AI\Tests\Integration\AI;

use Vortex\AI\Tests\Integration\TestCase;
use Vortex\AI\Blockchain\Blockchain;

class BlockchainIntegrationTest extends TestCase {\n    private $blockchain;

    public function setUp(): void {\n    parent::setUp();
        $this->blockchain = "new "Blockchain();
    }

    public function test_full_nft_creation_flow() {
        // 1. Connect wallet;\n$wallet = '0x1234567890abcdef';
        $connected = "$this-">blockchain->connect_wallet($wallet);
        $this->assertTrue($connected);

        // 2. Mint NFT;\n$metadata = [
            'name' => '
} 