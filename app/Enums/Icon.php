<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Icona;
use Filament\Support\Contracts\ScalableIcon;
use Filament\Support\Enums\IconSize;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use ToneGabes\Filament\Icons\Enums\Weight;

/**
 * Icone dell'applicazione.
 *
 * I casi rappresentano concetti dell'app, mentre phosphor()
 * associa ciascun caso alla relativa icona.
 */
enum Icon: string implements Icona, ScalableIcon
{
    // Navigazione.
    case Campaigns = 'campaigns';
    case Ledger = 'ledger';
    case Characters = 'characters';
    case Market = 'market';
    case Events = 'events';

    // Intestazione.
    case Notifications = 'notifications';
    case Menu = 'menu';

    // Menu utente.
    case Profile = 'profile';
    case Panel = 'panel';
    case Logout = 'logout';

    // Pannello.
    case Builds = 'builds';
    case Users = 'users';
    case Quests = 'quests';
    case Shop = 'shop';
    case Listings = 'listings';
    case Trades = 'trades';
    case Sessions = 'sessions';
    case DmRequests = 'dm-requests';
    case Bestiary = 'bestiary';
    case News = 'news';
    case Maps = 'maps';
    case Proposals = 'proposals';

    // Azioni.
    case Approve = 'approve';
    case Reject = 'reject';
    case Archive = 'archive';
    // Archivio personale, distinto da Archive del Pannello.
    case Stash = 'stash';
    case Unstash = 'unstash';
    case Featured = 'featured';
    case NotFeatured = 'not-featured';
    case Favorite = 'favorite';
    case NotFavorite = 'not-favorite';
    case Close = 'close';

    // Indicatori.
    case Guild = 'guild';
    case Fallen = 'fallen';
    case ArmorClass = 'armor-class';
    case HitPoints = 'hit-points';
    case Gold = 'gold';
    case Proficient = 'proficient';
    case Expert = 'expert';
    case GoTo = 'go-to';
    case Discover = 'discover';
    case Back = 'back';

    // Azioni e tipi di tiro del Turno.
    case Action = 'action';
    case BonusAction = 'bonus-action';
    case Reaction = 'reaction';
    case Passive = 'passive';
    case Attacks = 'attacks';
    case Cantrips = 'cantrips';
    case Privileges = 'privileges';
    case CastSpell = 'cast-spell';
    case General = 'general';
    case SpellAttack = 'spell-attack';
    case SpellSave = 'spell-save';
    case NoRoll = 'no-roll';
    case Upcast = 'upcast';
    case Talents = 'talents';

    // Menu della scheda personaggio.
    case Edit = 'edit';
    case LevelUp = 'level-up';
    case Loot = 'loot';
    case MagicItem = 'magic-item';
    case CharacterLedger = 'character-ledger';
    case Search = 'search';
    case Expand = 'expand';
    case Warnings = 'warnings';
    case Supervision = 'supervision';
    case Rank = 'rank';

    // Visibilità password.
    case ShowPassword = 'show-password';
    case HidePassword = 'hide-password';

    public function phosphor(): Phosphor
    {
        return match ($this) {
            self::Campaigns => Phosphor::FlagBannerFold,
            self::Ledger => Phosphor::BookBookmark,
            self::Characters => Phosphor::IdentificationBadge,
            self::Market => Phosphor::HandCoins,
            self::Events => Phosphor::BookmarkSimple,
            self::Notifications => Phosphor::Bell,
            self::Menu => Phosphor::DotsThreeBold,

            self::Profile => Phosphor::UserCircle,
            self::Panel => Phosphor::SlidersHorizontal,
            self::Logout => Phosphor::SignOut,

            self::Builds => Phosphor::Sparkle,
            self::Users => Phosphor::Users,
            self::Quests => Phosphor::SealQuestion,
            self::Shop => Phosphor::Storefront,
            self::Listings => Phosphor::Article,
            self::Trades => Phosphor::ArrowsLeftRight,
            self::Sessions => Phosphor::CalendarDots,
            self::DmRequests => Phosphor::ShieldCheck,
            self::Bestiary => Phosphor::Ghost,
            self::News => Phosphor::Newspaper,
            self::Maps => Phosphor::MapTrifold,
            self::Proposals => Phosphor::Tray,

            self::Approve => Phosphor::CheckCircle,
            self::Reject => Phosphor::XCircle,
            self::Archive => Phosphor::Archive,
            self::Stash => Phosphor::BoxArrowDown,
            self::Unstash => Phosphor::BoxArrowUp,
            self::Featured => Phosphor::Sparkle,
            self::NotFeatured => Phosphor::Minus,
            self::Favorite => Phosphor::StarFill,
            self::NotFavorite => Phosphor::Star,
            self::Close => Phosphor::X,

            self::Guild => Phosphor::ShieldCheckered,
            self::ArmorClass => Phosphor::Shield,
            self::Fallen => Phosphor::Skull,
            self::HitPoints => Phosphor::Heart,
            self::Gold => Phosphor::Coin,
            self::Proficient => Phosphor::CircleFill,
            self::Expert => Phosphor::StarFill,
            self::Action => Phosphor::HandFist,
            self::BonusAction => Phosphor::HandGrabbing,
            self::Reaction => Phosphor::Warning,
            self::Passive => Phosphor::HandPalm,
            self::Attacks => Phosphor::Sword,
            self::Cantrips => Phosphor::MagicWand,
            self::Privileges => Phosphor::Trophy,
            self::CastSpell => Phosphor::PersonSimpleThrow,
            self::General => Phosphor::UsersFour,
            self::SpellAttack => Phosphor::DiceFive,
            self::SpellSave => Phosphor::Shield,
            self::NoRoll => Phosphor::Prohibit,
            self::Upcast => Phosphor::ArrowUp,
            self::Talents => Phosphor::Ranking,

            self::Edit => Phosphor::PencilSimple,
            self::LevelUp => Phosphor::ArrowFatUp,
            self::Loot => Phosphor::Coin,
            self::MagicItem => Phosphor::MagicWand,
            self::CharacterLedger => Phosphor::Notebook,

            self::GoTo => Phosphor::ArrowRight,
            self::Discover => Phosphor::ArrowUpRight,
            self::Back => Phosphor::ArrowLeft,
            self::Search => Phosphor::MagnifyingGlass,
            self::Expand => Phosphor::CaretDown,
            self::Warnings => Phosphor::Warning,
            self::Supervision => Phosphor::Eye,
            self::Rank => Phosphor::MedalMilitary,

            self::ShowPassword => Phosphor::Eye,
            self::HidePassword => Phosphor::EyeClosed,
        };
    }

    // Peso predefinito delle icone.
    private const PESO = Weight::Duotone;

    public function blade(): string
    {
        $nome = $this->phosphor()->getLabel();

        // Evita di aggiungere un secondo peso alle icone che ne hanno già uno.
        foreach (Weight::cases() as $peso) {
            if ($peso !== Weight::Regular && str_ends_with($this->phosphor()->value, '-'.$peso->value)) {
                return $nome;
            }
        }

        return self::PESO === Weight::Regular ? $nome : $nome.'-'.self::PESO->value;
    }

    // La dimensione non modifica l'icona Phosphor.
    public function getIconForSize(IconSize $size): string
    {
        return $this->blade();
    }
}
