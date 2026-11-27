"""Équivalent Python de DesimoneService, basé sur ffbb-data-client.

    pip install ffbb-data-client

Le client interroge directement les API FFBB (Directus + Meilisearch),
sans passer par ffbb-api.desimone.fr. Les tokens sont résolus
automatiquement par FFBBDataClient.create().
"""

from __future__ import annotations

from typing import Any

from ffbb_data_client import FFBBDataClient

import sqlite3

class FFBBServiceError(RuntimeError):
    """Erreur lors de l'appel aux données FFBB."""


def _get(obj: Any, name: str, default: Any = None) -> Any:
    """Lit un champ sur un modèle Pydantic ou sur un dict."""
    if obj is None:
        return default
    if isinstance(obj, dict):
        return obj.get(name, default)
    return getattr(obj, name, default)


def _dump(obj: Any) -> Any:
    """Convertit un modèle Pydantic en dict/list natifs (équivalent de ->json())."""
    if hasattr(obj, "model_dump"):
        return obj.model_dump()
    if isinstance(obj, (list, tuple)):
        return [_dump(o) for o in obj]
    return obj


class FFBBService:
    def __init__(self, client: FFBBDataClient | None = None) -> None:
        # Créé une seule fois : la résolution des tokens est coûteuse.
        self.client = client or FFBBDataClient.create()
        self.con = sqlite3.connect("database/database.sqlite")

    def close(self) :
        self.con.close()

    def get_club_id(self, search: str) -> int | None:
        """Retourne l'id du premier organisme (club) trouvé, nécessaire pour chaque requête."""
        try:
            result = self.client.search_organismes(search)
        except Exception as e:
            raise FFBBServiceError(f"Erreur FFBB : {e}") from e

        for hit in _get(result, "hits", None) or []:
            club_id = _get(hit, "id")
            if club_id is not None:
                return int(club_id)
        return None

    def get_teams(self, club_id: str | int) -> list:
        """Retourne les équipes (engagements) d'un club."""
        try:
            organisme = self.client.get_organisme(int(club_id))
        except Exception as e:
            raise FFBBServiceError(f"Erreur FFBB : {e}") from e

        teams = _get(organisme, "engagements")
        if not isinstance(teams, list):
            raise ValueError("La réponse FFBB ne contient pas les équipes du club.")
        return teams

    def get_poule(self, poule_id: str | int) -> Any:
        """Retourne les infos d'une poule, avec ses rencontres."""
        try:
            poule = self.client.get_poule(int(poule_id))
        except Exception as e:
            raise FFBBServiceError(f"Erreur FFBB : {e}") from e

        if not isinstance(_get(poule, "rencontres"), list):
            raise ValueError("La réponse FFBB ne contient pas les rencontres de la poule.")
        return poule

    def get_competition(self, competition_id: str | int) -> Any:
        """Retourne les infos d'une compétition."""
        try:
            return self.client.get_competition(int(competition_id))
        except Exception as e:
            raise FFBBServiceError(f"Erreur FFBB : {e}") from e

    def get_classement(self, poule_id: str | int) -> list:
        """Retourne le classement d'une poule.

        Pas d'endpoint /classement dédié repéré dans la doc du client :
        le classement est lu sur la poule (champ `classements`).
        """
        poule = self.get_poule(poule_id)
        ranking = _get(poule, "classements")
        if not isinstance(ranking, list):
            raise ValueError("La réponse FFBB ne contient pas le classement de la poule.")
        return ranking


    def refreshChampionships(self) :
        cur = self.con.cursor()

        for row in cur.execute("SELECT id,name,season,category,division,pool,status,source,external_id,pool_external_id FROM championships") :
            pouleId = row[8];
            competitionId = row[7];
            champId = row[1];
            ffbbcompet=self.get_competition(competitionId)
            for phase in ffbbcompet.phases :
                print(" phase {} nom:{} liveStat:{} phase_code:{}".format(p.id,p.nom,p.liveStat,p.phase_code))
                for poule in phase.poules :
                    if poule.id == pouleId :
                        print("  poule {} {} {} rencontres".format(champId,poule.id,poule.nom,len(poule.rencontres)))
                        self.refreshGames(competitionId,pouleId,poule.rencontres)


        con.close()


    def refreshGames(self,champId,competitionId,pouleId, ffbbrencontre) :

        gamedayId = self.updateOrCreateGameDay(ffbbrencontre)

        team1Id = self.updateOrCreateTeam(ffbbrencontre.idEngagementEquipe1)
        team2Id = self.updateOrCreateTeam(ffbbrencontre.idEngagementEquipe2)

        self.updateOrCreateGame(gamedayId,champId,team1Id,team2Id,ffbbrencontre)


    def updateOrCreateGameDay(self,champId,ffbbrencontres) :
        cur = self.con.cursor()

        res = cur.execute("SELECT id,championship_id,number,label,date,source,external_id FROM championship_gamedays WHERE championship_id={} and number={}",champId,ffbbrencontres.numeroJournee)
        cg = res.fetchone()
        gamedayId = None

        if cg is not None :
            gameId=cg[0]
            query=''
            if cg[5] != 'desimone' :
                print("if faut update source {}=>'desimone'".format(cg[2]))
                query+="source='desimone'"
            if pouleId==cg[6] :
                print("if faut update external_id {}=>'desimone'".format(cg[6],pouleId))
                query+="," if query
                query+="external_id={}".format(cg[6])

            if query :
                cur.execute("UPDATE championshipGames {} WHERE id={}".format(query,gameId))
        else :
            cur.execute("INSERT INTO championship_gamedays(championship_id,number,date,source,external_id) VALUES({},{},'{}','{}','desimone',{}) ".format(champId),ffbbrencontres.numeroJournee,ffbbrencontres.date_rencontre,ffbbrencontres.id)
            gamedayId = cur.lastrowid

        return gamedayId

    def updateOrCreateGame(self,champId,gamedayId,team1Id,team2Id,ffbbrencontre) :
        cur = self.con.cursor()
        res = cur.execute("SELECT id FROM championship_games WHERE championship_id={} AND external_id={}".format(champId,ffbbrencontre.id))
        cg = res.fetchone()
        gameId = None

        if cg is None :
            cur.execute("INSERT INTO championship_games(championship_id,name,source,external_id) VALUES({},'{}','desimone',{}) ".format(champId,ffbbteam.nom,ffbbteam.id))
            gameId = cur.lastrowid
        else :
            gameId=cg[0]

        return gameId


    def updateOrCreateTeam(self,champId,ffbbteam) :
        cur = self.con.cursor()
        res = cur.execute("SELECT id,championship_id,team_id,name,source,external_id FROM championship_teams WHERE championship_id={} AND external_id={}".format(champId,ffbbteam.id))

        ct = res.fetchone()
        teamId = None

        if cg is None :
            cur.execute("INSERT INTO championship_teams(championship_id,name,source,external_id) VALUES({},'{}','desimone',{}) ".format(champId,ffbbteam.nom,ffbbteam.id))
            teamId = cur.lastrowid
        else :
            teamId=ct[0]

        return teamId



if __name__ == "__main__":
    svc = FFBBService()

    #club_id = svc.get_club_id("Labarthe")
    #print("club_id :", club_id)
    club_id = 12374
    competitionid="200000002897360"
    #if club_id:
    #    for team in svc.get_teams(club_id):
    #        print(" -", _get(team, "nom") or _dump(team))

    # c = svc.get_competition(competitionid)
    # print("nom {} - S {} - {}".format(c.nom,c.sexe,c.saison))
    # for p in c.poules :
    #     print("  poule {} {}".format(p.id,p.nom))
    
    # for p in c.phases :
    #     print(" phase {} nom:{} liveStat:{} phase_code:{}".format(p.id,p.nom,p.liveStat,p.phase_code))
    #     for poule in p.poules :
    #         print("  poule {} {} {} rencontres".format(poule.id,poule.nom,len(poule.rencontres)))


    svc.refreshChampionships()
