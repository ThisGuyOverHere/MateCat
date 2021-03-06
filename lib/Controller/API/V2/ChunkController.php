<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 21/02/2017
 * Time: 10:39
 */

namespace API\V2;

use API\V2\Json\Chunk;
use API\V2\Validators\ChunkPasswordValidator;
use Chunks_ChunkStruct;

class ChunkController extends KleinController {

    /**
     * @var Chunks_ChunkStruct
     */
    protected $chunk;

    /**
     * @param Chunks_ChunkStruct $chunk
     *
     * @return $this
     */
    public function setChunk( $chunk ) {
        $this->chunk = $chunk;

        return $this;
    }

    /**
     * @throws \Exception
     * @throws \Exceptions\NotFoundError
     */
    public function show() {

        $format = new Chunk();

        $format->setUser( $this->user );
        $format->setCalledFromApi( true );

        $this->response->json( $format->renderOne($this->chunk) );

    }

    public function cancel() {
        return $this->changeStatus( \Constants_JobStatus::STATUS_CANCELLED );
    }

    public function archive() {
        return $this->changeStatus( \Constants_JobStatus::STATUS_ARCHIVED );
    }

    public function active() {
        return $this->changeStatus( \Constants_JobStatus::STATUS_ACTIVE );
    }

    private function changeStatus( $status ) {

        updateJobsStatus( "", $this->chunk->id, $status, $this->chunk->password );
        $this->response->json( [ 'code' => 1, 'data' => "OK", 'status' => $status ] );
    }

    protected function afterConstruct() {
        $Validator = new ChunkPasswordValidator( $this ) ;
        $Controller = $this;
        $Validator->onSuccess( function () use ( $Validator, $Controller ) {
            $Controller->setChunk( $Validator->getChunk() );
        } );
        $this->appendValidator( $Validator );
    }

}