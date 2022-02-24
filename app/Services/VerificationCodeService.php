<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;

class VerificationCodeService
{
    private static function hashAlgorithm()
    {
        return "sha256";
    }
    private static function secretKey()
    {
        return env("APP_KEY", "default_secret_key");
    }

    public static function getHashedCode(Request $request)
    {
        $validator = Validator::make($request->only("hashed_code"), [
            "hashed_code" => "nullable|string"
        ]);
        $hashedCode = Cookie::has("hashed_code") ? Cookie::get("hashed_code")
            : $validator->validated()["hashed_code"] ?? null;
        return $hashedCode;
    }

    public static function createHash($email, $verificationCode, $ttl) // insert TTL in minutes (example : $ttl = 3)  
    {
        $expires = $ttl <= 120 ? now()->addMinutes($ttl)->timestamp : $ttl; // Expires after in Minutes (max 120 or 2 hours)  
        $data = $email . $verificationCode . $expires;
        $hash = hash_hmac(self::hashAlgorithm(), $data, self::secretKey()); // Create SHA256 hash of the data with key
        return $hash . "." . $expires;
    }

    public static function verify($email, $verificationCode, $hash)
    {
        // Seperate Hash value and expires from the hash returned from the user
        if (strpos($hash, '.') !== false) {
            $hashdata = explode(".", $hash);
            // Check if expiry time has passed
            if ($hashdata[1] < time()) {
                return false;
            }
            // Calculate new hash with the same key and the same algorithm
            // $newHash = self::createHash($email, $verificationCode, $hashdata[1]);

            $data = $email . $verificationCode . $hashdata[1];
            $newHash = hash_hmac(self::hashAlgorithm(), $data, self::secretKey());
            // Match the hashes
            if (hash_equals($newHash, $hashdata[0])) {
                return true;
            }
        } else {
            return false;
        }
    }

    public static function generate($length)
    {
        $chars = '0123456789';
        $charLength = strlen($chars);
        $verificationCodeString = '';
        for ($i = 0; $i < $length; $i++) {
            $verificationCodeString .= $chars[rand(0, $charLength - 1)];
        }
        return $verificationCodeString;
    }
}
