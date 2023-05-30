<?php

namespace DachcomDigital\Payum\Powerpay\Transaction;

class Transaction
{
    protected mixed $id;
    protected int|float $amount;
    protected ?string $currency;
    protected ?string $clientIp = null;
    protected ?string $language = null;
    protected ?string $gender = null;
    protected ?string $firstName = null;
    protected ?string $lastName = null;
    protected ?string $street = null;
    protected ?string $city = null;
    protected ?string $zip = null;
    protected ?string $phoneNumber = null;
    protected ?string $country = null;
    protected ?string $email = null;
    protected ?string $birthdate = null;

    public function getId(): mixed
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    public function getAmount(): float|int
    {
        return $this->amount;
    }

    public function setAmount(float|int $amount): void
    {
        $this->amount = $amount;
    }

    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    public function setClientIp(?string $clientIp): void
    {
        $this->clientIp = $clientIp;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): void
    {
        $this->zip = $zip;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthdate;
    }

    public function setBirthDate(?string $birthDate): void
    {
        $this->birthdate = $birthDate;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->getId(),
            'amount'      => $this->getAmount(),
            'currency'    => $this->getCurrency(),
            'clientIp'    => $this->getClientIp(),
            'language'    => $this->getLanguage(),
            'gender'      => $this->getGender(),
            'firstName'   => $this->getFirstName(),
            'lastName'    => $this->getLastName(),
            'street'      => $this->getStreet(),
            'city'        => $this->getCity(),
            'zip'         => $this->getZip(),
            'phoneNumber' => $this->getPhoneNumber(),
            'country'     => $this->getCountry(),
            'email'       => $this->getEmail(),
            'birthdate'   => $this->getBirthDate(),
        ];
    }
}