<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
    OneToOne,
};

/**
 * SignupOption model.
 * Contains the possible options of a field of type ``option''.
 */
#[Entity]
class SignupOption
{
    /**
     * ID for the field.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected ?int $id = null;

    /**
     * Field that the option belongs to.
     */
    #[ManyToOne(
        targetEntity: SignupField::class,
        cascade: ["persist"],
        inversedBy: "options",
    )]
    #[JoinColumn(
        name: "field_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected SignupField $field;

    /**
     * The value of the option.
     */
    #[OneToOne(
        targetEntity: LocalisedText::class,
        cascade: ["persist"],
        orphanRemoval: true,
    )]
    protected LocalisedText $value;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return SignupField
     */
    public function getField(): SignupField
    {
        return $this->field;
    }

    /**
     * Set the field the option belongs to.
     *
     * @param SignupField $field
     */
    public function setField(SignupField $field): void
    {
        $this->field = $field;
    }

    /**
     * @return LocalisedText
     */
    public function getValue(): LocalisedText
    {
        return $this->value;
    }

    /**
     * Set the value of the option.
     *
     * @param LocalisedText $value
     */
    public function setValue(LocalisedText $value): void
    {
        $this->value = $value->copy();
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'value' => $this->getValue()->getValueNL(),
            'valueEn' => $this->getValue()->getValueEN(),
        ];
    }
}
