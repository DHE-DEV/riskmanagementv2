package de.passolution.keycloak.md5;

import org.keycloak.credential.hash.PasswordHashProvider;
import org.keycloak.models.PasswordPolicy;
import org.keycloak.models.credential.PasswordCredentialModel;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

public class Md5PasswordHashProvider implements PasswordHashProvider {

    private final String providerId;

    public Md5PasswordHashProvider(String providerId) {
        this.providerId = providerId;
    }

    @Override
    public boolean policyCheck(PasswordPolicy policy, PasswordCredentialModel credential) {
        return providerId.equals(credential.getPasswordCredentialData().getAlgorithm());
    }

    @Override
    public PasswordCredentialModel encodedCredential(String rawPassword, int iterations) {
        String hash = md5(rawPassword);
        return PasswordCredentialModel.createFromValues(
                providerId,
                new byte[0],
                1,
                hash
        );
    }

    @Override
    public boolean verify(String rawPassword, PasswordCredentialModel credential) {
        String storedHash = credential.getPasswordSecretData().getValue();
        if (storedHash == null || storedHash.isEmpty()) {
            return false;
        }

        String inputHash = md5(rawPassword);
        return storedHash.equalsIgnoreCase(inputHash);
    }

    private String md5(String input) {
        try {
            MessageDigest md = MessageDigest.getInstance("MD5");
            byte[] digest = md.digest(input.getBytes());
            BigInteger bigInt = new BigInteger(1, digest);
            String hash = bigInt.toString(16);
            // Pad with leading zeros to 32 characters
            while (hash.length() < 32) {
                hash = "0" + hash;
            }
            return hash;
        } catch (NoSuchAlgorithmException e) {
            throw new RuntimeException("MD5 algorithm not available", e);
        }
    }

    @Override
    public void close() {
        // nothing to close
    }
}
