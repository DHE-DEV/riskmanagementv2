package de.passolution.keycloak.bcrypt;

import org.bouncycastle.crypto.generators.OpenBSDBCrypt;
import org.keycloak.credential.hash.PasswordHashProvider;
import org.keycloak.models.PasswordPolicy;
import org.keycloak.models.credential.PasswordCredentialModel;

public class BcryptPasswordHashProvider implements PasswordHashProvider {

    private final String providerId;
    private final int defaultCost;

    public BcryptPasswordHashProvider(String providerId, int defaultCost) {
        this.providerId = providerId;
        this.defaultCost = defaultCost;
    }

    @Override
    public boolean policyCheck(PasswordPolicy policy, PasswordCredentialModel credential) {
        int cost = policy != null ? policy.getHashIterations() : -1;
        if (cost <= 0) {
            cost = defaultCost;
        }
        return credential.getPasswordCredentialData().getHashIterations() == cost
                && providerId.equals(credential.getPasswordCredentialData().getAlgorithm());
    }

    @Override
    public PasswordCredentialModel encodedCredential(String rawPassword, int iterations) {
        int cost = iterations > 0 ? iterations : defaultCost;
        // Generate a random 16-byte salt
        byte[] salt = new byte[16];
        new java.security.SecureRandom().nextBytes(salt);
        String hash = OpenBSDBCrypt.generate(rawPassword.toCharArray(), salt, cost);

        return PasswordCredentialModel.createFromValues(
                providerId,
                salt,
                cost,
                hash
        );
    }

    @Override
    public boolean verify(String rawPassword, PasswordCredentialModel credential) {
        String storedHash = credential.getPasswordSecretData().getValue();
        if (storedHash == null || storedHash.isEmpty()) {
            return false;
        }

        // Normalize: $2y$ (PHP/Laravel) and $2b$ are compatible with $2a$
        storedHash = storedHash.replace("$2y$", "$2a$").replace("$2b$", "$2a$");

        try {
            return OpenBSDBCrypt.checkPassword(storedHash, rawPassword.toCharArray());
        } catch (Exception e) {
            return false;
        }
    }

    @Override
    public void close() {
        // nothing to close
    }
}
