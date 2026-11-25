<?php

namespace App\Livewire\Game;

use Livewire\Component;

use App\Models\Game;
use App\Models\GameMemberOption;
use App\Models\GameOption;
use App\Models\Place;
use App\Models\User;

use App\Notifications\GameNotification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Edit extends Component
{
    public Game $game;
    public bool $editingGame = false;
    public string $gameTeamHome = '';
    public string $gameTeamAway = '';
    public string $gameDate = '';
    public ?int $gamePlaceId = null;
    public string $gameRendezvous = '';
    public ?string $gameScoreHome = '';
    public ?string $gameScoreAway = '';
    public ?string $gameCommentaire = '';
    public ?int $gameNumero = null;

    public $options;

    public string $message = '';
    public $members;
    public $selected;
    public $places;
    public $otms;


    public function mount()
    {
        $this->gameTeamHome = $this->game->team_home===null?'':$this->game->team_home;
        $this->gameTeamAway = $this->game->team_away===null?'':$this->game->team_away;
        $this->gameDate = $this->game->date;
        $this->gamePlaceId = $this->game->place_id;
        $this->gameRendezvous = $this->game->rendezvous ?? '';
        $this->gameScoreHome = $this->game->score_home ?? '';
        $this->gameScoreAway = $this->game->score_away ?? '';
        $this->gameCommentaire = $this->game->commentaire ?? '';
        $this->gameNumero = $this->game->numero ?? 0;
        
        $this->loaddata();
    }

    private function loaddata() {
        $this->members = $this->game->members()
                ->with(['gameOptions' => function ($query) {
                    $query->where('game_id', $this->game->id);
                }])
                ->with(['options' => function($query) {
                    $query->wherePivot('game_id',$this->game->id);
                }])
                ->orderBy('type')
                ->get()
                ->sortBy([["type"],["licensee.firstname"]]);

        $this->options = GameOption::where("team_id",$this->game->team_id)
                        ->orderBy('order')
                        ->get();

        $opts = $this->options->filter(function ($option) {
            return in_array(
                mb_strtolower($option->name),
                ['numero', 'numéro']
            );
        });
        if ($opts->count() > 0) {
            foreach ($this->members as $member) {
                $hasGeneratedNumber = $member->gameOptions->contains(function ($gameOption) use ($opts) {
                    return $opts->contains('id', $gameOption->game_option_id)
                        && !empty($gameOption->value);
                });

                if (! $hasGeneratedNumber) {
                    $member->gameOptions->push(GameMemberOption::updateOrCreate(
                        [
                            'game_id' => $this->game->id,
                            'member_id' => $member->id,
                            'game_option_id' => $opts->first()->id,
                        ],
                        [
                            'value' => $member->numero,
                        ]
                    ));
                }
            }
        }
        $this->otms = [];
        foreach($this->members as $member) {
           if ($member->isotm()) {
            $this->otms[] = $member;
           }
        }

        $this->generateMessage();
        
        $this->places = Place::orderby('city')->get();

    }


    private function generateMessage() {
        if ($this->game->team->msg_convocation) {
            $this->message = $this->game->team->msg_convocation;

            $sels='';
            $notsels='';
            foreach($this->members as $member) {
                if ($member->pivot->selected) {
                    if ($sels !== "") { $sels.=", ";}
                    $sels .= $member->licensee->firstname;
                } else {
                    if ($notsels !== "") { $notsels.=", ";}
                    $notsels .= $member->licensee->firstname;
                }
            }
            $this->message = str_replace('%SELECTION%',$sels,$this->message);
            $this->message = str_replace('%NONSELECTION%',$notsels,$this->message);

            $jourmatch = $this->game->date
                ? \Carbon\Carbon::parse($this->game->date)->translatedFormat('l d F')
                : '';
            $this->message = str_replace('%JOURMATCH%', $jourmatch, $this->message);

            $this->message = str_replace('%RENDEZVOUS%', (string) ($this->game->rendezvous ?? ''), $this->message);


        } else {
            $this->message = '';
        }

    }


    public function generateFeuille() {
        $spreadsheet = new Spreadsheet();
        $inputFileName = storage_path('app/' .  config("app.template_file"));
        $spreadsheet = IOFactory::load($inputFileName);
        $activeWorksheet = $spreadsheet->getActiveSheet();
        $activeWorksheet->setCellValue('C3', $this->game->numero);
        $date = \Carbon\Carbon::parse($this->game->date)->format("d/m/Y"); 
        $activeWorksheet->setCellValue('E3', $date);

        $oppositionA = $this->game->members()
                                  ->with([
                                      'gameOptions' => function ($query) {
                                          $query->where('game_id', $this->game->id);
                                      },
                                      'options' => function ($query) {
                                          $query->where('type', GameOption::TYPE_NUM);
                                      },
                                  ])
                                  ->whereHas('oppositionOptions', function ($query) {
                    $query->where('game_id', $this->game->id)
                        ->where('value', 'A');
                })->get();

        $oppositionB = $this->game->members()
                                  ->with([
                                      'gameOptions' => function ($query) {
                                          $query->where('game_id', $this->game->id);
                                      },
                                      'options' => function ($query) {
                                          $query->where('type', GameOption::TYPE_NUM);
                                      },
                                  ])
                                  ->whereHas('oppositionOptions', function ($query) {
                    $query->where('game_id', $this->game->id)
                        ->where('value', 'B');
                })->get();

        $this->writeopposition($activeWorksheet,$oppositionB,8);
        $this->writeopposition($activeWorksheet,$oppositionA,18);
        $this->writestaff($activeWorksheet,$this->game->team->coaches()->get(),27);
        /*$this->writestaff($activeWorksheet,$this->game->otm,32);*/
        $writer = new Xlsx($spreadsheet);

        $filename = 'feuille_match_' . $this->game->numero . '.xlsx';

        $this->loaddata();

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
        
    }

    private function getMemberGameNumber($member): ?string
    {
        $numberOption = $member->gameOptions
            ->first(function ($gameOption) {
                $option = $this->options?->firstWhere('id', $gameOption->game_option_id);

                return $gameOption->game_id === $this->game->id
                    && $option !== null
                    && in_array(mb_strtolower($option->name), ['numero', 'numéro']);
            });

        if ($numberOption !== null && $numberOption->value !== null && $numberOption->value !== '') {
            return (string) $numberOption->value;
        }

        if ($member->numero !== null && $member->numero !== '') {
            return (string) $member->numero;
        }

        return null;
    }

    private function writeopposition($sheet,$opp,$start) 
    {
        $joueurs = $opp->map(function ($joueur) {
            return [
                'joueur' => $joueur,
                'numero' => $this->getMemberGameNumber($joueur),
            ];
        })->sortBy(function ($joueur) {
            return $joueur['numero'] === null ? PHP_INT_MAX : (int) $joueur['numero'];
        });

        $numligne=$start;
        foreach($joueurs as $joueurData) {
            $joueur = $joueurData['joueur'];
            $sheet->setCellValue("A$numligne", $joueurData['numero']);
            
            $sheet->setCellValue("B$numligne", $joueur->licensee->licence);
            $sheet->setCellValue("C$numligne", $joueur->licensee->name);
            $sheet->setCellValue("D$numligne", $joueur->licensee->firstname);
            $numligne++;
        }
    }

    private function writestaff($sheet,$staffs,$start) 
    {
        $numligne=$start;
        foreach($staffs as $s) {            
            $sheet->setCellValue("A$numligne", $s->licensee->licence);
            $sheet->setCellValue("C$numligne", $s->licensee->name);
            $sheet->setCellValue("D$numligne", $s->licensee->firstname);
            $numligne++;
        }
    }    


    public function toggleEditingGame()
    {
        $this->editingGame = !$this->editingGame;
        if ($this->editingGame) {
            $this->gameTeamHome = $this->game->team_home===null?'':$this->game->team_home;
            $this->gameTeamAway = $this->game->team_away===null?'':$this->game->team_away;
            $this->gameDate = $this->game->date;
            $this->gamePlaceId = $this->game->place_id;
            $this->gameRendezvous = $this->game->rendezvous ?? '';
            $this->gameScoreHome = $this->game->score_home ?? '';
            $this->gameScoreAway = $this->game->score_away ?? '';
            $this->gameCommentaire = $this->game->commentaire ?? ''; 
            $this->gameNumero = $this->game->numero ?? 0;          
        }
        $this->loaddata();
    }

    public function updateGame()
    {
        $place = $this->gamePlaceId ? Place::find($this->gamePlaceId) : null;

        $this->game->update([
            'team_home' => $this->gameTeamHome,
            'team_away' => $this->gameTeamAway,
            'date' => $this->gameDate,
            'place_id' => $this->gamePlaceId,
            'rendezvous' => $this->gameRendezvous,
            'score_home' => $this->gameScoreHome,
            'score_away' => $this->gameScoreAway,
            'commentaire' => $this->gameCommentaire,
            'numero' => $this->gameNumero
        ]);
        $this->editingGame = false;
        $this->loaddata();
    }

    public function setAvailability($memberId,$value)
    {
        $this->game->members()->updateExistingPivot($memberId, [
            'availability' => $value
        ]);
        $this->loaddata();
    }

    public function toggleSelection($memberId)
    {
        $pivot = $this->game->members()->find($memberId)->pivot;

        $this->game->members()->updateExistingPivot($memberId, [
            'selected' => !$pivot->selected
        ]);

        $this->loaddata();
    }

    public function toggleCalled($memberId)
    {
        $pivot = $this->game->members()->find($memberId)->pivot;

        $this->game->members()->updateExistingPivot($memberId, [
            'called' => !$pivot->called
        ]);

        $this->loaddata();

    }

    public function setGameOption($memberId, $optionId, $value)
    {
        GameMemberOption::updateOrCreate(
            [
                'game_id' => $this->game->id,
                'member_id' => $memberId,
                'game_option_id' => $optionId,
            ],
            [
                'value' => $value,
            ]
        );
         $this->loaddata();
    }

    public function deleteGame() {
        $team_id=$this->game->team_id;
        $this->game->delete();
        redirect(route('team.show',[$team_id]));
    }

    public function sendNotification()
    {
       \Log::info("sendNotification");

       $usersToNotify = User::whereHas('licensees.members', function ($query) {
           $query->whereHas('games', function ($subQuery) {
               $subQuery->where('games.id', $this->game->id)
                   ->where(function ($availabilityQuery) {
                       $availabilityQuery->where('availability', 'maybe')
                           ->orWhereNull('availability');
                   });
           });
       })->get();

       $usersToNotify->each->notify(new GameNotification($this->game));
       $this->loaddata();
    }

    public function copyAndOpenWhatsapp(): void
    {
        $this->dispatch(
            'copy-and-open-whatsapp',
            message: $this->message,
            link: $this->game->team->whatsapp
        );
        $this->loaddata();
    }


    public function render()
    {
        $isDomicile = $this->game->isDomicile();
        $hasStats = false;
        foreach($this->options as $option) {
            if ($option->isDisplayStat()) {
                $hasStats = true;
                break;
            }
        }

        return view('livewire.game.edit', [
            'isDomicile' => $isDomicile,
            'hasStats' => $hasStats,
        ])->layout('layouts.app');
    }
}
