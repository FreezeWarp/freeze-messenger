<?php
/*
 *
                $this->tableQuoteStart = '';
                $this->tableQuoteEnd = '';
                $this->tableAliasQuoteStart = '';
                $this->tableAliasQuoteEnd = '';
                $this->columnQuoteStart = '';
                $this->columnQuoteEnd = '';
                $this->columnAliasQuoteStart = '';
                $this->columnAliasQuoteEnd = '';
                $this->databaseQuoteStart = '';
                $this->databaseQuoteEnd = '';
                $this->databaseAliasQuoteStart = '';
                $this->databaseAliasQuoteEnd = '';
                $this->stringQuoteStart = '';
                $this->stringQuoteEnd = '';

 *                 switch ($operation) {
                    case 'connect':
                        try {
                            $this->connection = new PDO("mysql:dbname=$args[5];host=$args[1]:$args[2]", $args[3], $args[4]);
                        } catch (PDOException $e) {
                            $this->connection->errorCode = $e->getMessage();
                            $this->triggerError('Connect Error: ' . $this->connection->errorCode, false, 'connection');

                            return false;
                        }
                        $this->activeDatabase = $args[5];

                        return $this->connection;
                    break;

                    case 'version':
                        $this->setDatabaseVersion($this->connection->getAttribute(PDO::ATTR_SERVER_VERSION));
                    break;

                    case 'error':
                        return $this->connection->errorCode;
                    break;

                    case 'selectdb':
                        return $this->rawQuery("USE " . $this->formatValue("database", $args[1]));
                    break; // TODO test

                    case 'close':
                        unset($this->connection);

                        return true;
                    break;

                    case 'escape':
                        switch ($args[2]) {
                            case DatabaseTypeType::string:
                            case DatabaseTypeType::search:
                                return $this->connection->quote($args[1], PDO::PARAM_STR);
                            break;
                            case DatabaseTypeType::integer:
                            case DatabaseTypeType::timestamp:
                                return $this->connection->quote($args[1], PDO::PARAM_STR);
                            break;
                            case DatabaseTypeType::column:
                            case 'columnA':
                            case 'table':
                            case 'tableA':
                            case 'database':
                                return $args[1];
                            break;
                            default:
                                $this->triggerError('Invalid context.', ['arguments' => $args], 'validation');
                            break;
                        }
                    break; // Note: long-term, we should implement this using prepared statements.

                    case 'query':
                        return $this->connection->query($args[1]);
                    break;

                    case 'insertId':
                        return $this->connection->lastInsertId();
                    break;

                    case 'startTrans':
                        $this->connection->beginTransaction();
                    break; // Use start_transaction in PHP 5.5

                    case 'endTrans':
                        $this->connection->commit();
                    break;

                    case 'rollbackTrans':
                        $this->connection->rollBack();
                    break;

                    default:
                        $this->triggerError("[Function Map] Unrecognised Operation", ['operation' => $operation], 'validation');
                    break;
                }
 */