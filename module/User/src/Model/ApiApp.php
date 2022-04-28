<?php

namespace User\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
};
use User\Model\Enums\JWTClaims;

/**
 * ApiApp model.
 */
#[Entity]
class ApiApp
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Application ID.
     */
    #[Column(type: "string")]
    protected string $appId;

    /**
     * Application secret.
     */
    #[Column(type: "string")]
    protected string $secret;

    /**
     * Callback URL.
     */
    #[Column(type: "string")]
    protected string $callback;

    /**
     * The claims that will be present in the JWT. If `null` only the lidnr will be passed along.
     *
     * @var JWTClaims[]|null $claims
     */
    #[Column(
        type: "simple_array",
        nullable: true,
        insertable: false,
        updatable: false,
        enumType: JWTClaims::class,
    )]
    protected array $claims;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getCallback(): string
    {
        return $this->callback;
    }

    public function getClaims(): ?array
    {
        return $this->claims;
    }
}
