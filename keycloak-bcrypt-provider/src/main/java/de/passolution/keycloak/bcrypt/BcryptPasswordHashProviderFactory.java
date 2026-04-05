package de.passolution.keycloak.bcrypt;

import org.keycloak.Config;
import org.keycloak.credential.hash.PasswordHashProvider;
import org.keycloak.credential.hash.PasswordHashProviderFactory;
import org.keycloak.models.KeycloakSession;
import org.keycloak.models.KeycloakSessionFactory;

public class BcryptPasswordHashProviderFactory implements PasswordHashProviderFactory {

    public static final String ID = "bcrypt";
    public static final int DEFAULT_COST = 10;

    @Override
    public PasswordHashProvider create(KeycloakSession session) {
        return new BcryptPasswordHashProvider(ID, DEFAULT_COST);
    }

    @Override
    public void init(Config.Scope config) {
        // no config needed
    }

    @Override
    public void postInit(KeycloakSessionFactory factory) {
        // nothing to do
    }

    @Override
    public String getId() {
        return ID;
    }

    @Override
    public void close() {
        // nothing to close
    }
}
