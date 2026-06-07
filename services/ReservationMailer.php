<?php
declare(strict_types=1);

namespace Services;

// Composition et envoi des emails liés aux réservations (notification aux parents,
// reçu de confirmation, demande de validation par lien). S'appuie sur les fonctions
// globales cfg() / send_mail() / abs_url() ; aucun envoi si les adresses ne sont pas
// configurées (send_mail renvoie alors false).
final class ReservationMailer
{
    private function siteName(): string
    {
        return (string) cfg('site_title', 'Liste de naissance');
    }

    // Prévient les parents (destinataire « email_to ») d'une réservation confirmée.
    public function notifyOwners(string $itemName, string $guestName, string $guestEmail, int $qty): void
    {
        $to = trim((string) cfg('email_to', ''));
        if ($to === '') {
            return;
        }
        $subject = "Nouvelle réservation : " . $itemName;
        $lines = [
            "Bonne nouvelle ! Une réservation vient d'être faite sur votre liste de naissance.",
            "",
            "Cadeau   : " . $itemName,
            "Quantité : " . $qty,
            "De       : " . $guestName,
            "Email    : " . ($guestEmail !== '' ? $guestEmail : "(non renseigné)"),
            "",
            "— " . $this->siteName(),
        ];
        // Reply-To sur l'email de la personne (si fourni) pour pouvoir lui répondre.
        send_mail($to, $subject, implode("\n", $lines), $guestEmail !== '' ? $guestEmail : null);
    }

    // Reçu de confirmation à la personne, avec le lien privé de gestion/annulation.
    public function sendReceipt(string $itemName, string $guestName, string $email, int $qty, string $token): bool
    {
        $manageUrl = abs_url('mes-reservations') . '?t=' . rawurlencode($token);
        $subject = "Confirmation de votre réservation — " . $this->siteName();
        $lines = [
            "Bonjour " . $guestName . ",",
            "",
            "Votre réservation est bien enregistrée :",
            "  • " . $itemName . ($qty > 1 ? " (×" . $qty . ")" : ""),
            "",
            "Pour voir ou annuler vos réservations à tout moment, utilisez ce lien privé :",
            $manageUrl,
            "",
            "Merci du fond du cœur 💛",
            "— " . $this->siteName(),
        ];
        return send_mail($email, $subject, implode("\n", $lines));
    }

    // Demande de validation : la réservation n'est prise en compte qu'après le clic.
    public function sendConfirmationRequest(string $itemName, string $guestName, string $email, int $qty, string $token): bool
    {
        $confirmUrl = abs_url('confirmer') . '?t=' . rawurlencode($token);
        $subject = "Validez votre réservation — " . $this->siteName();
        $lines = [
            "Bonjour " . $guestName . ",",
            "",
            "Vous avez choisi d'offrir :",
            "  • " . $itemName . ($qty > 1 ? " (×" . $qty . ")" : ""),
            "",
            "Il reste une étape : cliquez sur ce lien pour valider votre réservation.",
            "Tant que vous ne l'avez pas validée, elle n'est pas prise en compte.",
            "",
            $confirmUrl,
            "",
            "Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.",
            "— " . $this->siteName(),
        ];
        return send_mail($email, $subject, implode("\n", $lines));
    }
}
