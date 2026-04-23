export type PostgresType =
  | 'integer' | 'bigint' | 'decimal' | 'real'
  | 'varchar' | 'text' | 'char'
  | 'boolean'
  | 'timestamp' | 'date' | 'time'
  | 'uuid'
  | 'jsonb' | 'json'
  | 'text_array' | 'integer_array' | 'uuid_array'
  | 'inet' | 'cidr'

export type ValidationPresetType =
  | 'required' | 'min_length' | 'max_length' | 'min_value' | 'max_value'
  | 'integer' | 'numeric' | 'regex' | 'unique' | 'exists'
  | 'email' | 'url' | 'uuid' | 'date' | 'boolean'
  | 'in_list' | 'alpha' | 'alpha_num' | 'alpha_dash'

export interface SchemaInfo {
  name: string
  tables: TableInfo[]
}

export interface TableInfo {
  name: string
  schema: string
  rowCount: number
  columns: ColumnInfo[]
}

export interface ColumnInfo {
  name: string
  type: string
  nullable: boolean
  defaultValue: string | null
  isPrimaryKey: boolean
  isForeignKey: boolean
  isUnique: boolean
  foreignKey: { table: string; column: string; schema: string } | null
}

export interface TableDataResponse {
  table: string
  schema: string
  totalRows: number
  columns: string[]
  rows: Record<string, unknown>[]
  pagination: {
    page: number
    perPage: number
    totalPages: number
    totalRows: number
  }
}

export interface ColumnDefinition {
  name: string
  type: PostgresType
  length: number | null
  nullable: boolean
  defaultValue: string | null
  isPrimaryKey: boolean
  foreignKey: { table: string; column: string } | null
}

export interface ValidationConfig {
  preset: ValidationPresetType
  enabled: boolean
  value?: string | number | null
}

export interface ColumnValidations {
  columnName: string
  presets: ValidationConfig[]
}

export interface MigrationInfo {
  id: string
  batch: number
  name: string
  operation: string
  tableName: string
  schemaName: string
  status: 'pending' | 'executed' | 'failed' | 'rolled_back'
  sqlUp: string
  sqlDown: string
  errorMessage: string | null
  executedAt: string | null
  createdAt: string
}
