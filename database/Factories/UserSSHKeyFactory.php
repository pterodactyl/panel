<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserSSHKeyFactory extends Factory
{
    /**
     * Returns a fake public key for use on the system.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOaXIq09NH4a93EVdrvHYiZ67Wj+GBEBQ9ou4W0qSYm2',
            'fingerprint' => 'T2b3VnvHWUmfhDOFLqzZg2VfLgqJhWxzrMUy8WZ+V8M',
        ];
    }

    /**
     * Returns a DSA public key.
     */
    public function dsa(): self
    {
        return $this->state([
            'public_key' => 'ssh-dss AAAAB3NzaC1kc3MAAACBAPfiWwEFvBOafdUmHDPjXsUttt+65FHSZSCVVeEFOTaL7Y3d0CJyrtck8KS1vmXHSb8QFBY2B1yVSb/reaQvNreWZN3KDYfLbF57/zimBn+IrHrJR+ZglhOxDRHoGPWK7q9jYIrOLwoOjkNKXxz1eOHKUgufFfSNtIRLycEXczLrAAAAFQC6LnBErezotG52jN4JostfC/TfEwAAAIACuTxRzYFDXHAxFICeqqY9w+y+v2yQfdeQ1BgCq2GMagUYfOdqnjizTO9M614r/nXZK1SV10TqhUcQtkJzDQIUtBqzBF5cIC/1cIFKzXi5rNHs8Y4bz/PBD+EbQJdiy+1so1oi790r710bqnkzTravAOJ5rGyfuQRLt+f+kuS9NAAAAIEA7tjGtJuXGUtPIXfnrMYS1iOWryO4irqnvaWfel002/DaGaNjRghNe/cUBYlAsjPhGJ1F7BQlLAY1koliTY6l0svs7ZPBM5QOumrr8OaNXGGVIq/RkkxuZHmRoUL2qH3DGYaktPUn4vFPliiAmGWOHAEu1K6B4g4vG/SKgMRpIvc=',
            'fingerprint' => '9Fb/RODt9N6aldcB+lc6ih0ovr2G/JUjts2Wh21uxYI',
        ]);
    }

    /**
     * Returns an RSA public key, if "weak" is specified a 1024-bit RSA key is returned
     * which should fail validation when being stored.
     */
    public function rsa(bool $weak = false): self
    {
        if (!$weak) {
            return $this->state([
                'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQCo/YLm2SPSlOIG7AagBSmEe5c0b2PLPzUGFp3gARhD6n6ydBS40TlWzeg2qV95lh6fWBd8LsNgPOFmmuKuNZdBjAGeTY4gxKfHY1vK5/zOI4jPPqAMcCMNfd82aM97kx6dO8Hw1R79OyVpOZylpXLHayVPGHUK37Tpih4W7TeVSMrOqQF9F72lzhwgEtkdjm4gLBL6RpdNXrdnjIaNVnuade0Sb3w384vecZPe+S/997WirOMNy2JU4NdMHEnSjd1/i463RpN96AsXFAu1zl9nrXVhA7DVfSHoigXAqbs/xav8PRpLgAKjYpPohxQ9Nu6tP5jRUhfWdYwNFFp/aWloD/0JdP9LqcBBc9sO9TLkz3fBiUf11VM/QT1UhO84G+ahMxVn95jA472VPUe8uKff69lzbvSavEE6qcQX2TzVKOSi1E26Fzc6IZ/tHEuGEbGFxTsiQ1GysVZ0wr1p6ftd1SVqH5F/oaEK7UO8+xn/syEqaPf6A0eJWRNc0+lHA1sIRjmo9MOBvbkKExkx5JLHgGG81DYDFdZUuHY1BgSxJJcmNWV5BKRm350EbgRngoYI5tB3tCiZVW1PI8qyff9mBae11LY5GPlUeDnPrMvSdCKMIWrg7nC8SbndBCO3Fx4z7G2dTQy4ZmY7Ae9jR4pyg7tTOI3qgl8Z462GZi/jzw==',
                'fingerprint' => 'vjccQdGfqAvuEK3Bki1Ji6aOo3rIuGU0BGJ0ml4CjLQ',
            ]);
        }

        return $this->state([
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQC4VVsHFO5MxvCtAPyoKGANWyuwZ4fvllvFog5RJbfpDpw8etDFVGEXl+uRR8p79g9oV7MscoFo6HiWrJc4/4vlP665msjosILdIcbnuzMhvXnKisaGh9zflkpyR3KhUxoHxqYp2q8XtffjKKAHz1a8o7OUG6fwaKIqu+d0PoICZQ==',
            'fingerprint' => 'LQSzAAfAsbKpU94gojxXfjGjYcEv8UZIwyzwhcEr/aw',
        ]);
    }
}
