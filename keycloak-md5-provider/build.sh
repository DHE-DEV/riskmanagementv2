#!/bin/bash
# Build keycloak-md5-provider.jar using Docker
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

docker run --rm \
  -v "$SCRIPT_DIR:/build" \
  -w /build \
  quay.io/keycloak/keycloak:latest \
  bash -c '
    # Find Keycloak libs
    KC_HOME=/opt/keycloak
    CLASSPATH=$(find $KC_HOME/lib -name "keycloak-core-*.jar" -o -name "keycloak-server-spi-*.jar" -o -name "keycloak-server-spi-private-*.jar" | tr "\n" ":")

    # Compile
    mkdir -p /build/classes
    javac -cp "$CLASSPATH" \
      -d /build/classes \
      /build/src/main/java/de/passolution/keycloak/md5/*.java

    # Copy META-INF
    cp -r /build/src/main/resources/META-INF /build/classes/

    # Create JAR
    cd /build/classes
    jar cf /build/keycloak-md5-provider.jar .

    echo "BUILD SUCCESS: keycloak-md5-provider.jar"
  '
