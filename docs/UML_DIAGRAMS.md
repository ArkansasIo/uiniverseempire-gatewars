# UML Diagrams

> Class and sequence diagrams for the Universe Civilization: Empire at Wars
> backend and frontend. Rendered with Mermaid (supported by GitHub and
> docs renderers).

## 1. Class Diagram — Domain Layer

```mermaid
classDiagram
    class Chive {
        -db_link : mysqli
        -db_prefix : string
        -queryCount : int
        +__construct()
        +connectToDB() : void
        +connected() : bool
        +clean_sql(string) : string
        +query(string) : mysqli_result
    }

    class User {
        -userName : string
        -password : string
        -access : int
        -loggedIn : bool
        -userid : int
        -raceID : int
        -progress : int
        +__construct(user, pass)
        +isRealUser() : bool
        +isAllowed() : bool
        +logOut() : void
        +salt(uid, uname) : string
        +addUser() : bool
        +genUniqueLink(uid) : string
    }

    class Game {
        -gameTime : int
        -isRank : bool
        -actionTurns : int
        -inHand : int
        -inBank : int
        -nextTurn : int
        -numMessages : int
        -uid : int
        -rid : int
        -fields : array
        +nextTurn() : int
        +getRaces() : array
        +autoLoad() : string
        +messageCount() : int
        +baseVars() : array
        +getRanks() : array
        +getPersonnel() : array
        +getOfficers() : array
        +Rankings() : array
        +getUserInfo() : array
        +getUserPlanets() : array
        +turnUpdate() : void
        +attack_raid() : array
        +buyWeapons() : void
        +trainUnits() : void
        +untrainUnits() : void
        +buytech() : void
        +bank() : void
        +spy() : array
        +sabotage() : array
        +sendMessage() : bool
        +sendSupport() : bool
    }

    class ThemeSupport {
        <<static>>
        +normalizeTheme(string) : string
        +themeBodyClass(string) : string
        +brandName() : string
    }

    class Debug {
        <<static>>
        +out(mixed) : void
    }

    class SafeDbConnection {
        +isStub : bool
    }

    Chive <|-- User
    User <|-- Game
    Game ..> ThemeSupport : uses
    Chive --> SafeDbConnection : falls back to when no mysqli
```

## 2. Class Diagram — Frontend

```mermaid
classDiagram
    class window.mainJS {
        +sendData(page, type, id, atype, subject, message)
        +mainUpdate(page, title)
        +stylizeDiv()
        +setTheme(theme)
        +doForm(form)
    }
    class window.autoJS {
        +autoLoad()
        +statsTimer()
    }
    class window.trainJS {
        +trainUnits()
        +untrainUnits()
    }
    class window.searchJS {
        +userLookup(term)
    }
    class window.imagesJS {
        +MM_swapImgRestore()
        +MM_preloadImages()
    }
    class window.bbfixJS {
        +bb_init()
    }

    autoJS ..> mainJS : calls sendData('stats')
    index.tpl -- autoJS : body onload autoLoad()
    index.tpl -- mainJS : menu onclicks sendData
```

## 3. Sequence Diagram — Login Flow

```mermaid
sequenceDiagram
    participant B as Browser
    participant I as index.php
    participant U as User
    participant C as Chive
    participant D as MySQL

    B->>I: GET / (no session)
    I->>U: new User()
    U->>C: query(user by name)
    C->>D: SELECT * FROM users WHERE uname=?
    D-->>C: row + salt
    C-->>U: row
    U->>U: hash check, set loggedIn/session
    U-->>I: loggedIn=true
    I->>I: showPage() -> header.tpl+index.tpl+footer.tpl
    I-->>B: HTML shell
    B->>I: onload sendData('pages','get','empire','overview')
    I-->>B: empire overview fragment in #mainDisplay
    B->>I: autoLoad() -> stats.php every 15s
    I-->>B: JSON top-bar payload
```

## 4. Sequence Diagram — AJAX Page Navigation

```mermaid
sequenceDiagram
    participant B as Browser
    participant M as js/main.js
    participant X as modules/<page>.php
    participant G as Game
    participant D as MySQL

    B->>M: click menu item
    M->>X: POST/GET sendData('<module>', type, id, atype)
    X->>X: include config.php; login+time guard
    X->>G: new Game(); call gameplay action
    G->>D: SELECT/UPDATE
    D-->>G: result
    G-->>X: data
    X-->>M: HTML fragment (or text result)
    M->>M: stylizeDiv() inject into #mainDisplay
    M-->>B: render updated region
```

## 5. Sequence Diagram — Turn Tick (`turnUpdate()`)

```mermaid
sequenceDiagram
    participant C as Cron / 30min.php
    participant G as Game
    participant D as MySQL

    C->>G: turnUpdate()
    G->>G: nextTurn() = now + 30min
    G->>D: SELECT units, technology, bank, planets, userdata FOR UPDATE
    D-->>G: rows
    G->>G: income = f(race income_bonus, tech income, miners, planets, untrained)
    G->>G: unitProd = f(unitProd tech, untrained, training per turn)
    G->>G: turns = +1 per elapsed 30-min slot
    G->>D: UPDATE bank (onHand += income), units (untrained±), userdata (actionTurns)
    G->>D: INSERT actionlog summary
    G-->>C: done
```

## 6. Sequence Diagram — Cron Economy Tick (`game_tick.php`)

```mermaid
sequenceDiagram
    participant CR as cron (every 5 min)
    participant T as scripts/backend/game_tick.php
    participant P as player_resources
    participant H as hyperspace_transits
    participant D as MySQL

    CR->>T: php game_tick.php
    T->>T: parse --dry-run / --uid=N
    T->>D: SELECT uid, last_tick_at FROM player_resources
    T->>T: elapsed = now - last_tick_at; slots = floor(elapsed/1800)
    alt slots >= 1
        T->>P: UPDATE metal/crystal/deuterium/food/water/population += rate*slots
        T->>P: UPDATE energy (consumed vs produced)
        T->>P: UPDATE last_tick_at += slots*1800
    end
    T->>H: process enroute -> arrived transits
    T->>D: UPSERT app_server_jobs heartbeat
    T-->>CR: summary line (dry-run => no writes)
```

## 7. Sequence Diagram — Player Attack / Raid

```mermaid
sequenceDiagram
    participant B as Browser
    participant A as modules/attack.php
    participant G as Game
    participant D as MySQL

    B->>A: sendData('attack','get',uid,'attack')
    A->>G: new Game(); attack_raid(target)
    G->>D: SELECT defender units/power, defender resources
    G->>G: attackerPower vs defenderPower; ratios
    G->>G: compute kills both sides, loot (income/steal caps)
    G->>D: UPDATE both unit tables, bank, resources
    G->>D: INSERT actionlog for both players
    G-->>A: battle report
    A-->>B: rendered report fragment
```
