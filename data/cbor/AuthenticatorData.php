<?php

namespace WebAuthn\Attestation;
use WebAuthn\CBOR\CborDecoder;
use WebAuthn\Binary\ByteBuffer;

/**
 * @author Lukas Buchs
 * @license https://github.com/lbuchs/WebAuthn/blob/master/LICENSE MIT
 */
class AuthenticatorData {
	protected $_binary;
	protected $_rpIdHash;
	protected $_flags;
	protected $_signCount;
	protected $_attestedCredentialData;
	protected $_extensionData;

	// Cose encoded keys
	private static $_COSE_KTY = 1;
	private static $_COSE_ALG = 3;
	private static $_COSE_CRV = -1;
	private static $_COSE_X = -2;
	private static $_COSE_Y = -3;

	private static $_EC2_TYPE = 2;
	private static $_EC2_ES256 = -7;
	private static $_EC2_P256 = 1;

	/**
	 * Parsing the authenticatorData binary.
	 * @param string $binary
	 * @throws Exception
	 */
	public function __construct($binary) {
		if (!\is_string($binary) || \strlen($binary) < 37) {
			throw new Exception('Invalid authenticatorData input');
		}
		$this->_binary = $binary;

		// Read infos from binary
		// https://www.w3.org/TR/webauthn/#sec-authenticator-data

		// RP ID
		$this->_rpIdHash = \substr($binary, 0, 32);

		// flags (1 byte)
		$flags = \unpack('Cflags', \substr($binary, 32, 1))['flags'];
		$this->_flags = $this->_readFlags($flags);

		// signature counter: 32-bit unsigned big-endian integer.
		$this->_signCount = \unpack('Nsigncount', \substr($binary, 33, 4))['signcount'];

		$offset = 37;
		// https://www.w3.org/TR/webauthn/#sec-attested-credential-data
		if ($this->_flags->attestedDataIncluded) {
			$this->_attestedCredentialData = $this->_readAttestData($binary, $offset);
		}

		if ($this->_flags->extensionDataIncluded) {
			$this->_readExtensionData(\substr($binary, $offset));
		}
	}

	/**
	 * Authenticator Attestation Globally Unique Identifier, a unique number
	 * that identifies the model of the authenticator (not the specific instance
	 * of the authenticator)
	 * The aaguid may be 0 if the user is using a old u2f device and/or if
	 * the browser is using the fido-u2f format.
	 * @return string
	 * @throws Exception
	 */
	public function getAAGUID() {
		if (!($this->_attestedCredentialData instanceof \stdClass)) {
			throw  new Exception('credential data not included in authenticator data');
		}
		return $this->_attestedCredentialData->aaguid;
	}

	/**
	 * returns the authenticatorData as binary
	 * @return string
	 */
	public function getBinary() {
		return $this->_binary;
	}

	/**
	 * returns the credentialId
	 * @return string
	 * @throws Exception
	 */
	public function getCredentialId() {
		if (!($this->_attestedCredentialData instanceof \stdClass)) {
			throw  new Exception('credential id not included in authenticator data');
		}
		return $this->_attestedCredentialData->credentialId;
	}

	/**
	 * returns the public key in PEM format
	 * @return string
	 */
	public function getPublicKeyPem() {
		$der =
			$this->_der_sequence(
				$this->_der_sequence(
					$this->_der_oid("\x2A\x86\x48\xCE\x3D\x02\x01") . // OID 1.2.840.10045.2.1 ecPublicKey
					$this->_der_oid("\x2A\x86\x48\xCE\x3D\x03\x01\x07")  // 1.2.840.10045.3.1.7 prime256v1
				) .
				$this->_der_bitString($this->getPublicKeyU2F())
			);

		$pem = '-----BEGIN PUBLIC KEY-----' . "\n";
		$pem .= \chunk_split(\base64_encode($der), 64, "\n");
		$pem .= '-----END PUBLIC KEY-----' . "\n";
		return $pem;
	}

	/**
	 * returns the public key in U2F format
	 * @return string
	 * @throws Exception
	 */
	public function getPublicKeyU2F() {
		if (!($this->_attestedCredentialData instanceof \stdClass)) {
			throw  new Exception('credential data not included in authenticator data');
		}
		return "\x04" . // ECC uncompressed
				$this->_attestedCredentialData->credentialPublicKey->x .
				$this->_attestedCredentialData->credentialPublicKey->y;
	}

	/**
	 * returns the SHA256 hash of the relying party id (=hostname)
	 * @return string
	 */
	public function getRpIdHash() {
		return $this->_rpIdHash;
	}

	/**
	 * returns the sign counter
	 * @return int
	 */
	public function getSignCount() {
		return $this->_signCount;
	}



	public function getPubKeyDetails() {
		$temp = array();
		$kty = $this->_attestedCredentialData->credentialPublicKey->kty;
		$alg = $this->_attestedCredentialData->credentialPublicKey->alg;
		$crv = $this->_attestedCredentialData->credentialPublicKey->crv;
		switch ($kty) {
			case 2:
				$temp['kty'] = 'EC';
				switch ($alg) {
					case -7: $temp['alg'] = "ES256"; break;
					default: break;
				}
				switch ($crv) {
					case 1: $temp['crv'] = "P-256"; break;
					default: break;
				}
				break;
		}
		$x = base64_encode($this->_attestedCredentialData->credentialPublicKey->x);
		$url = strtr($x, '+/', '-_');
		$temp['x'] = rtrim($url, '=');
		$y = base64_encode($this->_attestedCredentialData->credentialPublicKey->y);
		$url = strtr($y, '+/', '-_');
		$temp['y'] = rtrim($url, '=');
		return $temp;
	}


	/**
	 * returns true if the user is present
	 * @return boolean
	 */
	public function getUserPresent() {
		return $this->_flags->userPresent;
	}

	/**
	 * returns true if the user is verified
	 * @return boolean
	 */
	public function getUserVerified() {
		return $this->_flags->userVerified;
	}

	// -----------------------------------------------
	// PRIVATE
	// -----------------------------------------------

	/**
	 * reads the flags from flag byte
	 * @param string $binFlag
	 * @return \stdClass
	 */
	private function _readFlags($binFlag) {
		$flags = new \stdClass();

		$flags->bit_0 = !!($binFlag & 1);
		$flags->bit_1 = !!($binFlag & 2);
		$flags->bit_2 = !!($binFlag & 4);
		$flags->bit_3 = !!($binFlag & 8);
		$flags->bit_4 = !!($binFlag & 16);
		$flags->bit_5 = !!($binFlag & 32);
		$flags->bit_6 = !!($binFlag & 64);
		$flags->bit_7 = !!($binFlag & 128);

		// named flags
		$flags->userPresent = $flags->bit_0;
		$flags->userVerified = $flags->bit_2;
		$flags->attestedDataIncluded = $flags->bit_6;
		$flags->extensionDataIncluded = $flags->bit_7;
		return $flags;
	}

	/**
	 * read attested data
	 * @param string $binary
	 * @param int $endOffset
	 * @return \stdClass
	 * @throws Exception
	 */
	private function _readAttestData($binary, &$endOffset) {
		$attestedCData = new \stdClass();
		if (\strlen($binary) <= 55) {
			throw new Exception('Attested data should be present but is missing');
		}

		// The AAGUID of the authenticator
		$attestedCData->aaguid = \substr($binary, 37, 16);

		//Byte length L of Credential ID, 16-bit unsigned big-endian integer.
		$length = \unpack('nlength', \substr($binary, 53, 2))['length'];
		$attestedCData->credentialId = \substr($binary, 55, $length);

		// set end offset
		$endOffset = 55 + $length;

		// extract public key
		$attestedCData->credentialPublicKey = $this->_readCredentialPublicKey($binary, 55 + $length, $endOffset);

		return $attestedCData;
	}

	/**
	 * reads COSE key-encoded elliptic curve public key in EC2 format
	 * @param string $binary
	 * @param int $endOffset
	 * @return \stdClass
	 * @throws Exception
	 */
	private function _readCredentialPublicKey($binary, $offset, &$endOffset) {
		$enc = CborDecoder::decodeInPlace($binary, $offset, $endOffset);

		// COSE key-encoded elliptic curve public key in EC2 format
		$credPKey = new \stdClass();
		$credPKey->kty = $enc[self::$_COSE_KTY];
		$credPKey->alg = $enc[self::$_COSE_ALG];
		$credPKey->crv = $enc[self::$_COSE_CRV];
		$credPKey->x   = $enc[self::$_COSE_X] instanceof ByteBuffer ? $enc[self::$_COSE_X]->getBinaryString() : null;
		$credPKey->y   = $enc[self::$_COSE_Y] instanceof ByteBuffer ? $enc[self::$_COSE_Y]->getBinaryString() : null;
		unset ($enc);

		// Validation
		if ($credPKey->kty !== self::$_EC2_TYPE) {
			throw new Exception('public key not in EC2 format');
		}

		if ($credPKey->alg !== self::$_EC2_ES256) {
			throw new Exception('signature algorithm not ES256');
		}

		if ($credPKey->crv !== self::$_EC2_P256) {
			throw new Exception('curve not P-256');
		}

		if (\strlen($credPKey->x) !== 32) {
			throw new Exception('Invalid X-coordinate');
		}

		if (\strlen($credPKey->y) !== 32) {
			throw new Exception('Invalid Y-coordinate');
		}

		return $credPKey;
	}

	/**
	 * reads cbor encoded extension data.
	 * @param string $binary
	 * @return array
	 * @throws Exception
	 */
	private function _readExtensionData($binary) {
		$ext = CborDecoder::decode($binary);
		if (!\is_array($ext)) {
			throw new Exception('invalid extension data');
		}

		return $ext;
	}


	// ---------------
	// DER functions
	// ---------------

	private function _der_length($len) {
		if ($len < 128) {
			return \chr($len);
		}
		$lenBytes = '';
		while ($len > 0) {
			$lenBytes = \chr($len % 256) . $lenBytes;
			$len = \intdiv($len, 256);
		}
		return \chr(0x80 | \strlen($lenBytes)) . $lenBytes;
	}

	private function _der_sequence($contents) {
		return "\x30" . $this->_der_length(\strlen($contents)) . $contents;
	}

	private function _der_oid($encoded) {
		return "\x06" . $this->_der_length(\strlen($encoded)) . $encoded;
	}

	private function _der_bitString($bytes) {
		return "\x03" . $this->_der_length(\strlen($bytes) + 1) . "\x00" . $bytes;
	}
}
