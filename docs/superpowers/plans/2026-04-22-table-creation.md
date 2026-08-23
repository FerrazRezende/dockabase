# Table Creation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add table creation with validation presets to the Schema Browser as a full-width sub-view wizard.

**Architecture:** Backend adds `ValidationPresetEnum` + `ValidationRuleMapper` (TDD). Frontend adds 5 new Vue components + modifies `SchemaBrowser.vue`. Existing `SchemaBuilderController@store()` handles the API — no route changes needed.

**Tech Stack:** Laravel 13 / PHP 8.4, Vue 3 / TypeScript / shadcn-vue, PostgreSQL

---

## File Structure

```
New files:
├── app/Enums/ValidationPresetEnum.php
├── app/Services/ValidationRuleMapper.php
├── tests/Unit/Enums/ValidationPresetEnumTest.php
├── tests/Unit/Services/ValidationRuleMapperTest.php
├── tests/Feature/SchemaBuilder/CreateTableTest.php
├── resources/js/components/schema/CreateTableWizard.vue
├── resources/js/components/schema/StepColumns.vue
├── resources/js/components/schema/StepValidations.vue
├── resources/js/components/schema/ColumnEditor.vue
├── resources/js/components/schema/ValidationPresets.vue

Modified files:
├── resources/js/components/schema/SchemaBrowser.vue  (add 'create-table' view + buttons)
├── resources/js/composables/useSchemaBrowser.ts       (add view type + helpers)
├── lang/pt.json                                       (add table creation keys)
├── lang/en.json                                       (add table creation keys)
├── lang/es.json                                       (add table creation keys)
```

---

## Task 1: ValidationPresetEnum (TDD)

**Files:**
- Create: `app/Enums/ValidationPresetEnum.php`
- Create: `tests/Unit/Enums/ValidationPresetEnumTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ValidationPresetEnum;
use Tests\TestCase;

class ValidationPresetEnumTest extends TestCase
{
    public function test_to_laravel_rule_required(): void
    {
        $this->assertSame('required', ValidationPresetEnum::REQUIRED->toLaravelRule());
    }

    public function test_to_laravel_rule_min_length_with_value(): void
    {
        $this->assertSame('min:3', ValidationPresetEnum::MIN_LENGTH->toLaravelRule('3'));
    }

    public function test_to_laravel_rule_max_length_with_value(): void
    {
        $this->assertSame('max:255', ValidationPresetEnum::MAX_LENGTH->toLaravelRule('255'));
    }

    public function test_to_laravel_rule_min_value(): void
    {
        $this->assertSame('min:0', ValidationPresetEnum::MIN_VALUE->toLaravelRule('0'));
    }

    public function test_to_laravel_rule_max_value(): void
    {
        $this->assertSame('max:999999.99', ValidationPresetEnum::MAX_VALUE->toLaravelRule('999999.99'));
    }

    public function test_to_laravel_rule_integer(): void
    {
        $this->assertSame('integer', ValidationPresetEnum::INTEGER->toLaravelRule());
    }

    public function test_to_laravel_rule_numeric(): void
    {
        $this->assertSame('numeric', ValidationPresetEnum::NUMERIC->toLaravelRule());
    }

    public function test_to_laravel_rule_regex(): void
    {
        $this->assertSame('regex:/^[a-z]+$/', ValidationPresetEnum::REGEX->toLaravelRule('/^[a-z]+$/'));
    }

    public function test_to_laravel_rule_unique_with_table_and_column(): void
    {
        $this->assertSame('unique:products,name', ValidationPresetEnum::UNIQUE->toLaravelRule(null, 'products', 'name'));
    }

    public function test_to_laravel_rule_exists_with_table_and_column(): void
    {
        $this->assertSame('exists:categories,id', ValidationPresetEnum::EXISTS->toLaravelRule('categories,id'));
    }

    public function test_to_laravel_rule_email(): void
    {
        $this->assertSame('email', ValidationPresetEnum::EMAIL->toLaravelRule());
    }

    public function test_to_laravel_rule_url(): void
    {
        $this->assertSame('url', ValidationPresetEnum::URL->toLaravelRule());
    }

    public function test_to_laravel_rule_uuid(): void
    {
        $this->assertSame('uuid', ValidationPresetEnum::UUID->toLaravelRule());
    }

    public function test_to_laravel_rule_date(): void
    {
        $this->assertSame('date', ValidationPresetEnum::DATE->toLaravelRule());
    }

    public function test_to_laravel_rule_boolean(): void
    {
        $this->assertSame('boolean', ValidationPresetEnum::BOOLEAN->toLaravelRule());
    }

    public function test_to_laravel_rule_in_list(): void
    {
        $this->assertSame('in:active,inactive,pending', ValidationPresetEnum::IN_LIST->toLaravelRule('active,inactive,pending'));
    }

    public function test_to_laravel_rule_alpha(): void
    {
        $this->assertSame('alpha', ValidationPresetEnum::ALPHA->toLaravelRule());
    }

    public function test_to_laravel_rule_alpha_num(): void
    {
        $this->assertSame('alpha_num', ValidationPresetEnum::ALPHA_NUM->toLaravelRule());
    }

    public function test_to_laravel_rule_alpha_dash(): void
    {
        $this->assertSame('alpha_dash', ValidationPresetEnum::ALPHA_DASH->toLaravelRule());
    }

    public function test_has_value_returns_true_for_presets_with_params(): void
    {
        $this->assertTrue(ValidationPresetEnum::MIN_LENGTH->hasValue());
        $this->assertTrue(ValidationPresetEnum::MAX_LENGTH->hasValue());
        $this->assertTrue(ValidationPresetEnum::MIN_VALUE->hasValue());
        $this->assertTrue(ValidationPresetEnum::MAX_VALUE->hasValue());
        $this->assertTrue(ValidationPresetEnum::REGEX->hasValue());
        $this->assertTrue(ValidationPresetEnum::EXISTS->hasValue());
        $this->assertTrue(ValidationPresetEnum::IN_LIST->hasValue());
    }

    public function test_has_value_returns_false_for_presets_without_params(): void
    {
        $this->assertFalse(ValidationPresetEnum::REQUIRED->hasValue());
        $this->assertFalse(ValidationPresetEnum::INTEGER->hasValue());
        $this->assertFalse(ValidationPresetEnum::EMAIL->hasValue());
        $this->assertFalse(ValidationPresetEnum::UNIQUE->hasValue());
    }

    public function test_applicable_categories_required_is_all(): void
    {
        $categories = ValidationPresetEnum::REQUIRED->applicableCategories();
        $this->assertContains('text', $categories);
        $this->assertContains('numeric', $categories);
        $this->assertContains('boolean', $categories);
        $this->assertContains('datetime', $categories);
        $this->assertContains('uuid', $categories);
        $this->assertContains('json', $categories);
        $this->assertContains('array', $categories);
        $this->assertContains('network', $categories);
    }

    public function test_applicable_categories_text_only(): void
    {
        $categories = ValidationPresetEnum::MIN_LENGTH->applicableCategories();
        $this->assertEquals(['text'], $categories);
    }

    public function test_applicable_categories_numeric_only(): void
    {
        $categories = ValidationPresetEnum::MIN_VALUE->applicableCategories();
        $this->assertEquals(['numeric'], $categories);
    }

    public function test_applicable_categories_boolean(): void
    {
        $categories = ValidationPresetEnum::BOOLEAN->applicableCategories();
        $this->assertEquals(['boolean'], $categories);
    }

    public function test_applicable_categories_datetime(): void
    {
        $categories = ValidationPresetEnum::DATE->applicableCategories();
        $this->assertEquals(['datetime'], $categories);
    }

    public function test_label_returns_human_readable(): void
    {
        $this->assertEquals('Required', ValidationPresetEnum::REQUIRED->label());
        $this->assertEquals('Min Length', ValidationPresetEnum::MIN_LENGTH->label());
        $this->assertEquals('Max Length', ValidationPresetEnum::MAX_LENGTH->label());
        $this->assertEquals('Min Value', ValidationPresetEnum::MIN_VALUE->label());
        $this->assertEquals('Max Value', ValidationPresetEnum::MAX_VALUE->label());
        $this->assertEquals('Regex', ValidationPresetEnum::REGEX->label());
        $this->assertEquals('Unique', ValidationPresetEnum::UNIQUE->label());
        $this->assertEquals('Exists', ValidationPresetEnum::EXISTS->label());
        $this->assertEquals('In List', ValidationPresetEnum::IN_LIST->label());
        $this->assertEquals('Alpha', ValidationPresetEnum::ALPHA->label());
        $this->assertEquals('Alpha Num', ValidationPresetEnum::ALPHA_NUM->label());
        $this->assertEquals('Alpha Dash', ValidationPresetEnum::ALPHA_DASH->label());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/ValidationPresetEnumTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Write implementation**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ValidationPresetEnum: string
{
    case REQUIRED = 'required';
    case MIN_LENGTH = 'min_length';
    case MAX_LENGTH = 'max_length';
    case MIN_VALUE = 'min_value';
    case MAX_VALUE = 'max_value';
    case INTEGER = 'integer';
    case NUMERIC = 'numeric';
    case REGEX = 'regex';
    case UNIQUE = 'unique';
    case EXISTS = 'exists';
    case EMAIL = 'email';
    case URL = 'url';
    case UUID = 'uuid';
    case DATE = 'date';
    case BOOLEAN = 'boolean';
    case IN_LIST = 'in_list';
    case ALPHA = 'alpha';
    case ALPHA_NUM = 'alpha_num';
    case ALPHA_DASH = 'alpha_dash';

    public function label(): string
    {
        return match ($this) {
            self::REQUIRED => 'Required',
            self::MIN_LENGTH => 'Min Length',
            self::MAX_LENGTH => 'Max Length',
            self::MIN_VALUE => 'Min Value',
            self::MAX_VALUE => 'Max Value',
            self::INTEGER => 'Integer',
            self::NUMERIC => 'Numeric',
            self::REGEX => 'Regex',
            self::UNIQUE => 'Unique',
            self::EXISTS => 'Exists',
            self::EMAIL => 'Email',
            self::URL => 'URL',
            self::UUID => 'UUID',
            self::DATE => 'Date',
            self::BOOLEAN => 'Boolean',
            self::IN_LIST => 'In List',
            self::ALPHA => 'Alpha',
            self::ALPHA_NUM => 'Alpha Num',
            self::ALPHA_DASH => 'Alpha Dash',
        };
    }

    public function hasValue(): bool
    {
        return in_array($this, [
            self::MIN_LENGTH, self::MAX_LENGTH,
            self::MIN_VALUE, self::MAX_VALUE,
            self::REGEX, self::EXISTS, self::IN_LIST,
        ], true);
    }

    public function applicableCategories(): array
    {
        $all = ['text', 'numeric', 'boolean', 'datetime', 'uuid', 'json', 'array', 'network'];

        return match ($this) {
            self::REQUIRED, self::NUMERIC => $all,
            self::MIN_LENGTH, self::MAX_LENGTH, self::REGEX,
            self::EMAIL, self::URL, self::UUID,
            self::ALPHA, self::ALPHA_NUM, self::ALPHA_DASH,
            self::IN_LIST => ['text'],
            self::MIN_VALUE, self::MAX_VALUE, self::INTEGER => ['numeric'],
            self::BOOLEAN => ['boolean'],
            self::DATE => ['datetime'],
            self::UNIQUE => $all,
            self::EXISTS => $all,
        };
    }

    public function toLaravelRule(?string $value = null, ?string $table = null, ?string $column = null): string
    {
        return match ($this) {
            self::REQUIRED => 'required',
            self::MIN_LENGTH => "min:{$value}",
            self::MAX_LENGTH => "max:{$value}",
            self::MIN_VALUE => "min:{$value}",
            self::MAX_VALUE => "max:{$value}",
            self::INTEGER => 'integer',
            self::NUMERIC => 'numeric',
            self::REGEX => "regex:{$value}",
            self::UNIQUE => "unique:{$table},{$column}",
            self::EXISTS => "exists:{$value}",
            self::EMAIL => 'email',
            self::URL => 'url',
            self::UUID => 'uuid',
            self::DATE => 'date',
            self::BOOLEAN => 'boolean',
            self::IN_LIST => "in:{$value}",
            self::ALPHA => 'alpha',
            self::ALPHA_NUM => 'alpha_num',
            self::ALPHA_DASH => 'alpha_dash',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/ValidationPresetEnumTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Enums/ValidationPresetEnum.php tests/Unit/Enums/ValidationPresetEnumTest.php
git commit -m "feat(schema-builder): add ValidationPresetEnum with Laravel rule mapping"
```

---

## Task 2: ValidationRuleMapper (TDD)

**Files:**
- Create: `app/Services/ValidationRuleMapper.php`
- Create: `tests/Unit/Services/ValidationRuleMapperTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ValidationPresetEnum;
use App\Services\ValidationRuleMapper;
use Tests\TestCase;

class ValidationRuleMapperTest extends TestCase
{
    public function test_to_laravel_rules_converts_json_to_rules(): void
    {
        $validations = [
            'name' => [
                'required' => true,
                'min_length' => '3',
                'max_length' => '255',
                'alpha' => true,
            ],
            'price' => [
                'required' => true,
                'min_value' => '0',
                'max_value' => '999999.99',
            ],
        ];

        $rules = ValidationRuleMapper::toLaravelRules($validations);

        $this->assertEquals([
            'name' => ['required', 'min:3', 'max:255', 'alpha'],
            'price' => ['required', 'min:0', 'max:999999.99'],
        ], $rules);
    }

    public function test_to_laravel_rules_skips_disabled_presets(): void
    {
        $validations = [
            'name' => [
                'required' => true,
                'min_length' => false,
                'alpha' => null,
            ],
        ];

        $rules = ValidationRuleMapper::toLaravelRules($validations);

        $this->assertEquals(['name' => ['required']], $rules);
    }

    public function test_to_laravel_rules_handles_unique_with_auto_table(): void
    {
        $validations = [
            'email' => [
                'required' => true,
                'unique' => true,
            ],
        ];

        $rules = ValidationRuleMapper::toLaravelRules($validations, 'users');

        $this->assertContains('unique:users,email', $rules['email']);
    }

    public function test_to_laravel_rules_handles_exists(): void
    {
        $validations = [
            'category_id' => [
                'exists' => 'categories,id',
            ],
        ];

        $rules = ValidationRuleMapper::toLaravelRules($validations);

        $this->assertEquals(['category_id' => ['exists:categories,id']], $rules);
    }

    public function test_to_laravel_rules_handles_empty_validations(): void
    {
        $this->assertEquals([], ValidationRuleMapper::toLaravelRules([]));
    }

    public function test_get_presets_for_category_returns_text_presets(): void
    {
        $presets = ValidationRuleMapper::getPresetsForCategory('text');

        $names = array_map(fn (ValidationPresetEnum $p) => $p->value, $presets);

        $this->assertContains('required', $names);
        $this->assertContains('min_length', $names);
        $this->assertContains('max_length', $names);
        $this->assertContains('email', $names);
        $this->assertContains('alpha', $names);
        $this->assertNotContains('min_value', $names);
        $this->assertNotContains('boolean', $names);
        $this->assertNotContains('date', $names);
    }

    public function test_get_presets_for_category_returns_numeric_presets(): void
    {
        $presets = ValidationRuleMapper::getPresetsForCategory('numeric');
        $names = array_map(fn (ValidationPresetEnum $p) => $p->value, $presets);

        $this->assertContains('required', $names);
        $this->assertContains('min_value', $names);
        $this->assertContains('max_value', $names);
        $this->assertContains('integer', $names);
        $this->assertNotContains('min_length', $names);
        $this->assertNotContains('email', $names);
    }

    public function test_get_presets_for_category_returns_boolean_presets(): void
    {
        $presets = ValidationRuleMapper::getPresetsForCategory('boolean');
        $names = array_map(fn (ValidationPresetEnum $p) => $p->value, $presets);

        $this->assertContains('required', $names);
        $this->assertContains('boolean', $names);
        $this->assertNotContains('min_length', $names);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/ValidationRuleMapperTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Write implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ValidationPresetEnum;

class ValidationRuleMapper
{
    /**
     * Convert JSON validations to Laravel rules array.
     *
     * @param  array<string, array<string, bool|numeric|string|null>>  $columnValidations
     * @return array<string, array<int, string>>
     */
    public static function toLaravelRules(array $columnValidations, ?string $tableName = null): array
    {
        $rules = [];

        foreach ($columnValidations as $columnName => $presets) {
            $columnRules = [];

            foreach ($presets as $presetName => $config) {
                if ($config === false || $config === null) {
                    continue;
                }

                $preset = ValidationPresetEnum::from($presetName);
                $value = is_bool($config) ? null : (string) $config;

                if ($preset === ValidationPresetEnum::UNIQUE) {
                    $columnRules[] = $preset->toLaravelRule(null, $tableName, $columnName);
                } else {
                    $columnRules[] = $preset->toLaravelRule($value);
                }
            }

            if ($columnRules !== []) {
                $rules[$columnName] = $columnRules;
            }
        }

        return $rules;
    }

    /**
     * Get validation presets applicable to a type category.
     *
     * @return array<int, ValidationPresetEnum>
     */
    public static function getPresetsForCategory(string $category): array
    {
        return array_values(
            array_filter(
                ValidationPresetEnum::cases(),
                fn (ValidationPresetEnum $preset): bool => in_array($category, $preset->applicableCategories(), true),
            )
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/ValidationRuleMapperTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/ValidationRuleMapper.php tests/Unit/Services/ValidationRuleMapperTest.php
git commit -m "feat(schema-builder): add ValidationRuleMapper for JSON to Laravel rules conversion"
```

---

## Task 3: ColumnEditor.vue

**Files:**
- Create: `resources/js/components/schema/ColumnEditor.vue`

Depends on: existing types in `resources/js/types/schema.ts` (`ColumnDefinition`, `PostgresType`)

- [ ] **Step 1: Create ColumnEditor component**

Single row editor for one column. Props: `column` (ColumnDefinition), `index` (number), `canRemove` (boolean). Emits: `update`, `remove`.

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { X } from 'lucide-vue-next'
import type { ColumnDefinition, PostgresType } from '@/types/schema'

interface Props {
  column: ColumnDefinition
  index: number
  canRemove: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  update: [index: number, column: ColumnDefinition]
  remove: [index: number]
}>()

const typeGroups: Record<string, { label: string; types: { value: PostgresType; label: string }[] }> = {
  numeric: {
    label: 'Numeric',
    types: [
      { value: 'integer', label: 'Integer' },
      { value: 'bigint', label: 'Bigint' },
      { value: 'decimal', label: 'Decimal' },
      { value: 'real', label: 'Real' },
    ],
  },
  text: {
    label: 'Text',
    types: [
      { value: 'varchar', label: 'Varchar' },
      { value: 'text', label: 'Text' },
      { value: 'char', label: 'Char' },
    ],
  },
  boolean: {
    label: 'Boolean',
    types: [{ value: 'boolean', label: 'Boolean' }],
  },
  datetime: {
    label: 'Date/Time',
    types: [
      { value: 'timestamp', label: 'Timestamp' },
      { value: 'date', label: 'Date' },
      { value: 'time', label: 'Time' },
    ],
  },
  uuid: {
    label: 'UUID',
    types: [{ value: 'uuid', label: 'UUID' }],
  },
  json: {
    label: 'JSON',
    types: [
      { value: 'jsonb', label: 'JSONB' },
      { value: 'json', label: 'JSON' },
    ],
  },
  array: {
    label: 'Array',
    types: [
      { value: 'text_array', label: 'Text[]' },
      { value: 'integer_array', label: 'Integer[]' },
      { value: 'uuid_array', label: 'UUID[]' },
    ],
  },
  network: {
    label: 'Network',
    types: [
      { value: 'inet', label: 'INET' },
      { value: 'cidr', label: 'CIDR' },
    ],
  },
}

const needsLength = computed(() => props.column.type === 'varchar' || props.column.type === 'char')

const update = (field: keyof ColumnDefinition, value: unknown) => {
  emit('update', props.index, { ...props.column, [field]: value })
}
</script>

<template>
  <tr class="group border-b last:border-b-0 hover:bg-accent/30 transition-colors">
    <td class="py-2 px-3">
      <Input
        :model-value="column.name"
        placeholder="column_name"
        class="h-8 text-sm font-mono"
        @update:model-value="update('name', $event)"
      />
    </td>
    <td class="py-2 px-3">
      <Select :model-value="column.type" @update:model-value="update('type', $event)">
        <SelectTrigger class="h-8 text-sm">
          <SelectValue placeholder="Type" />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup v-for="(group, key) in typeGroups" :key="key">
            <SelectLabel>{{ group.label }}</SelectLabel>
            <SelectItem v-for="t in group.types" :key="t.value" :value="t.value">
              {{ t.label }}
            </SelectItem>
          </SelectGroup>
        </SelectContent>
      </Select>
    </td>
    <td class="py-2 px-3">
      <Input
        v-if="needsLength"
        :model-value="column.length ?? ''"
        type="number"
        placeholder="255"
        class="h-8 w-20 text-sm"
        @update:model-value="update('length', $event ? Number($event) : null)"
      />
      <span v-else class="text-xs text-muted-foreground">—</span>
    </td>
    <td class="py-2 px-3 text-center">
      <Checkbox
        :checked="column.nullable"
        @update:checked="update('nullable', $event)"
      />
    </td>
    <td class="py-2 px-3">
      <Input
        :model-value="column.defaultValue ?? ''"
        placeholder="gen_random_uuid()"
        class="h-8 text-sm font-mono"
        @update:model-value="update('defaultValue', $event || null)"
      />
    </td>
    <td class="py-2 px-3 text-center">
      <Checkbox
        :checked="column.isPrimaryKey"
        @update:checked="update('isPrimaryKey', $event)"
      />
    </td>
    <td class="py-2 px-3 w-10">
      <Button
        v-if="canRemove"
        variant="ghost"
        size="icon"
        class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity"
        @click="emit('remove', index)"
      >
        <X class="h-3.5 w-3.5 text-destructive" />
      </Button>
    </td>
  </tr>
</template>
```

- [ ] **Step 2: Verify file compiles**

Run: `npx vue-tsc --noEmit` (or check in browser)
Expected: No type errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/schema/ColumnEditor.vue
git commit -m "feat(schema-builder): add ColumnEditor component for table column editing"
```

---

## Task 4: StepColumns.vue

**Files:**
- Create: `resources/js/components/schema/StepColumns.vue`

Depends on: Task 3 (ColumnEditor.vue), existing types

- [ ] **Step 1: Create StepColumns component**

Editable table of columns. Manages reactive `columns` array. Props: `modelValue` (ColumnDefinition[]). Emits: `update:modelValue`.

```vue
<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Plus } from 'lucide-vue-next'
import ColumnEditor from '@/components/schema/ColumnEditor.vue'
import type { ColumnDefinition, PostgresType } from '@/types/schema'

interface Props {
  modelValue: ColumnDefinition[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: ColumnDefinition[]]
}>()

const createDefaultColumn = (): ColumnDefinition => ({
  name: '',
  type: 'varchar' as PostgresType,
  length: 255,
  nullable: false,
  defaultValue: null,
  isPrimaryKey: false,
  foreignKey: null,
})

const addColumn = () => {
  emit('update:modelValue', [...props.modelValue, createDefaultColumn()])
}

const updateColumn = (index: number, column: ColumnDefinition) => {
  const updated = [...props.modelValue]
  updated[index] = column
  emit('update:modelValue', updated)
}

const removeColumn = (index: number) => {
  const updated = props.modelValue.filter((_, i) => i !== index)
  emit('update:modelValue', updated)
}
</script>

<template>
  <div>
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead class="text-xs">{{ __('Name') }}</TableHead>
          <TableHead class="text-xs">{{ __('Type') }}</TableHead>
          <TableHead class="text-xs w-20">{{ __('Length') }}</TableHead>
          <TableHead class="text-xs w-16 text-center">{{ __('Nullable') }}</TableHead>
          <TableHead class="text-xs">{{ __('Default') }}</TableHead>
          <TableHead class="text-xs w-16 text-center">{{ __('PK') }}</TableHead>
          <TableHead class="w-10" />
        </TableRow>
      </TableHeader>
      <TableBody>
        <ColumnEditor
          v-for="(column, index) in modelValue"
          :key="index"
          :column="column"
          :index="index"
          :can-remove="modelValue.length > 1"
          @update="updateColumn"
          @remove="removeColumn"
        />
      </TableBody>
    </Table>

    <Button variant="outline" size="sm" class="mt-3" @click="addColumn">
      <Plus class="h-3.5 w-3.5 mr-1.5" />
      {{ __('Add Column') }}
    </Button>
  </div>
</template>
```

- [ ] **Step 2: Verify compiles**

Run: `npx vue-tsc --noEmit`
Expected: No type errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/schema/StepColumns.vue
git commit -m "feat(schema-builder): add StepColumns component for column definitions"
```

---

## Task 5: ValidationPresets.vue

**Files:**
- Create: `resources/js/components/schema/ValidationPresets.vue`

Depends on: existing types (`ValidationPresetType`, `ValidationConfig`)

- [ ] **Step 1: Create ValidationPresets component**

Checklist of validation presets filtered by column type category. Props: `columnName`, `columnType`, `modelValue`. Emits: `update:modelValue`.

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { PostgresType, ValidationConfig, ValidationPresetType } from '@/types/schema'

interface Props {
  columnName: string
  columnType: PostgresType
  modelValue: Record<string, boolean | number | string>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: Record<string, boolean | number | string>]
}>()

const typeCategoryMap: Record<string, string> = {
  integer: 'numeric', bigint: 'numeric', decimal: 'numeric', real: 'numeric',
  varchar: 'text', text: 'text', char: 'text',
  boolean: 'boolean',
  timestamp: 'datetime', date: 'datetime', time: 'datetime',
  uuid: 'uuid',
  jsonb: 'json', json: 'json',
  text_array: 'array', integer_array: 'array', uuid_array: 'array',
  inet: 'network', cidr: 'network',
}

const category = computed(() => typeCategoryMap[props.columnType] ?? 'text')

interface PresetDef {
  type: ValidationPresetType
  label: string
  hasValue: boolean
  valuePlaceholder?: string
}

const allPresets: PresetDef[] = [
  { type: 'required', label: 'Required', hasValue: false },
  { type: 'min_length', label: 'Min Length', hasValue: true, valuePlaceholder: '3' },
  { type: 'max_length', label: 'Max Length', hasValue: true, valuePlaceholder: '255' },
  { type: 'min_value', label: 'Min Value', hasValue: true, valuePlaceholder: '0' },
  { type: 'max_value', label: 'Max Value', hasValue: true, valuePlaceholder: '999' },
  { type: 'integer', label: 'Must be integer', hasValue: false },
  { type: 'numeric', label: 'Must be numeric', hasValue: false },
  { type: 'regex', label: 'Regex', hasValue: true, valuePlaceholder: '/^[a-z]+$/' },
  { type: 'unique', label: 'Unique in table', hasValue: false },
  { type: 'exists', label: 'Exists in table', hasValue: true, valuePlaceholder: 'table,column' },
  { type: 'email', label: 'Must be email', hasValue: false },
  { type: 'url', label: 'Must be URL', hasValue: false },
  { type: 'uuid', label: 'Must be UUID', hasValue: false },
  { type: 'date', label: 'Must be date', hasValue: false },
  { type: 'boolean', label: 'Must be boolean', hasValue: false },
  { type: 'in_list', label: 'In list', hasValue: true, valuePlaceholder: 'a,b,c' },
  { type: 'alpha', label: 'Only letters', hasValue: false },
  { type: 'alpha_num', label: 'Letters + numbers', hasValue: false },
  { type: 'alpha_dash', label: 'Letters + dash/underscore', hasValue: false },
]

const categoryPresets: Record<string, ValidationPresetType[]> = {
  text: ['required', 'min_length', 'max_length', 'numeric', 'regex', 'unique', 'exists', 'email', 'url', 'uuid', 'in_list', 'alpha', 'alpha_num', 'alpha_dash'],
  numeric: ['required', 'min_value', 'max_value', 'integer', 'numeric', 'unique'],
  boolean: ['required', 'boolean'],
  datetime: ['required', 'date', 'unique'],
  uuid: ['required', 'unique'],
  json: ['required'],
  array: ['required'],
  network: ['required'],
}

const applicablePresets = computed(() => {
  const allowed = categoryPresets[category.value] ?? ['required']
  return allPresets.filter(p => allowed.includes(p.type))
})

const isEnabled = (type: ValidationPresetType): boolean => {
  const val = props.modelValue[type]
  return val !== undefined && val !== false && val !== null
}

const getValue = (type: ValidationPresetType): string => {
  const val = props.modelValue[type]
  if (val === true || val === undefined || val === null) return ''
  return String(val)
}

const toggle = (type: ValidationPresetType, checked: boolean) => {
  const updated = { ...props.modelValue }
  if (checked) {
    const preset = allPresets.find(p => p.type === type)
    updated[type] = preset?.hasValue ? '' : true
  } else {
    delete updated[type]
  }
  emit('update:modelValue', updated)
}

const setValue = (type: ValidationPresetType, value: string) => {
  const updated = { ...props.modelValue }
  updated[type] = value
  emit('update:modelValue', updated)
}
</script>

<template>
  <div class="space-y-3">
    <p class="text-sm font-medium">{{ columnName }} <span class="text-muted-foreground font-normal">({{ columnType }})</span></p>
    <div class="grid gap-2.5 sm:grid-cols-2">
      <div
        v-for="preset in applicablePresets"
        :key="preset.type"
        class="flex items-center gap-3 rounded-lg border px-3 py-2"
        :class="isEnabled(preset.type) ? 'border-primary/40 bg-primary/5' : 'border-border'"
      >
        <Checkbox
          :checked="isEnabled(preset.type)"
          @update:checked="toggle(preset.type, $event)"
        />
        <Label class="text-sm cursor-pointer flex-1" @click="toggle(preset.type, !isEnabled(preset.type))">
          {{ __(preset.label) }}
        </Label>
        <Input
          v-if="preset.hasValue && isEnabled(preset.type)"
          :model-value="getValue(preset.type)"
          :placeholder="preset.valuePlaceholder"
          class="h-7 w-32 text-xs font-mono"
          @update:model-value="setValue(preset.type, $event)"
        />
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Verify compiles**

Run: `npx vue-tsc --noEmit`
Expected: No type errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/schema/ValidationPresets.vue
git commit -m "feat(schema-builder): add ValidationPresets component for per-column rules"
```

---

## Task 6: StepValidations.vue

**Files:**
- Create: `resources/js/components/schema/StepValidations.vue`

Depends on: Task 5 (ValidationPresets.vue)

- [ ] **Step 1: Create StepValidations component**

Shows validation presets for each column. Props: `columns`, `modelValue`. Emits: `update:modelValue`.

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { ChevronDown, ChevronRight } from 'lucide-vue-next'
import ValidationPresets from '@/components/schema/ValidationPresets.vue'
import type { ColumnDefinition } from '@/types/schema'

interface Props {
  columns: ColumnDefinition[]
  modelValue: Record<string, Record<string, boolean | number | string>>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: Record<string, Record<string, boolean | number | string>>]
}>()

const expandedColumns = ref<Set<string>>(new Set(props.columns.map(c => c.name)))

const toggleExpand = (name: string) => {
  const updated = new Set(expandedColumns.value)
  if (updated.has(name)) {
    updated.delete(name)
  } else {
    updated.add(name)
  }
  expandedColumns.value = updated
}

const updateColumnValidations = (columnName: string, presets: Record<string, boolean | number | string>) => {
  emit('update:modelValue', { ...props.modelValue, [columnName]: presets })
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="column in columns"
      :key="column.name"
      class="rounded-lg border"
    >
      <button
        class="flex items-center gap-2 w-full px-4 py-3 text-left hover:bg-accent/30 transition-colors"
        @click="toggleExpand(column.name)"
      >
        <ChevronDown v-if="expandedColumns.has(column.name)" class="h-4 w-4 text-muted-foreground" />
        <ChevronRight v-else class="h-4 w-4 text-muted-foreground" />
        <span class="text-sm font-medium">{{ column.name }}</span>
        <span class="text-xs text-muted-foreground">({{ column.type }})</span>
        <span v-if="modelValue[column.name] && Object.keys(modelValue[column.name]).length > 0" class="ml-auto text-xs text-primary">
          {{ Object.keys(modelValue[column.name]).length }} {{ __('rules') }}
        </span>
      </button>
      <div v-if="expandedColumns.has(column.name)" class="px-4 pb-4 pt-1 border-t">
        <ValidationPresets
          :column-name="column.name"
          :column-type="column.type"
          :model-value="modelValue[column.name] ?? {}"
          @update:model-value="updateColumnValidations(column.name, $event)"
        />
      </div>
    </div>

    <p v-if="columns.length === 0" class="text-sm text-muted-foreground text-center py-4">
      {{ __('No columns defined. Go back to Step 1 to add columns.') }}
    </p>
  </div>
</template>
```

- [ ] **Step 2: Verify compiles**

Run: `npx vue-tsc --noEmit`
Expected: No type errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/schema/StepValidations.vue
git commit -m "feat(schema-builder): add StepValidations component for column validations"
```

---

## Task 7: CreateTableWizard.vue

**Files:**
- Create: `resources/js/components/schema/CreateTableWizard.vue`

Depends on: Task 4 (StepColumns), Task 6 (StepValidations)

- [ ] **Step 1: Create CreateTableWizard component**

Full-width sub-view container with step navigation, schema selector, and submit logic.

```vue
<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { useRouter } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Loader2, ChevronLeft } from 'lucide-vue-next'
import StepColumns from '@/components/schema/StepColumns.vue'
import StepValidations from '@/components/schema/StepValidations.vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import type { ColumnDefinition, PostgresType } from '@/types/schema'

interface SchemaOption {
  name: string
}

interface Props {
  databaseId: string
  schemas: SchemaOption[]
  preSelectedSchema?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  cancel: []
  created: []
}>()

const toast = useToast()

const currentStep = ref(1)
const tableName = ref('')
const selectedSchema = ref(props.preSelectedSchema ?? '')
const submitting = ref(false)

const columns = ref<ColumnDefinition[]>([
  {
    name: 'id',
    type: 'uuid' as PostgresType,
    length: null,
    nullable: false,
    defaultValue: 'gen_random_uuid()',
    isPrimaryKey: true,
    foreignKey: null,
  },
])

const validations = reactive<Record<string, Record<string, boolean | number | string>>>({})

const canProceedToStep2 = computed(() => {
  return tableName.value.trim() !== '' &&
    selectedSchema.value !== '' &&
    columns.value.length > 0 &&
    columns.value.every(c => c.name.trim() !== '')
})

const canSubmit = computed(() => canProceedToStep2.value)

const submit = async () => {
  if (!canSubmit.value) return

  submitting.value = true
  try {
    await axios.post(route('app.databases.tables.store', props.databaseId), {
      name: tableName.value,
      schema: selectedSchema.value,
      columns: columns.value.map(c => ({
        name: c.name,
        type: c.type,
        length: c.length,
        nullable: c.nullable,
        default_value: c.defaultValue,
        is_primary_key: c.isPrimaryKey,
        foreign_key: c.foreignKey,
      })),
      validations: validations,
    })

    toast.success(__('Table created successfully'))
    emit('created')
  } catch (error: any) {
    const message = error.response?.data?.message || __('Failed to create table')
    toast.error(message)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <button
        class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
        @click="emit('cancel')"
      >
        <ChevronLeft class="h-4 w-4" />
        {{ __('Back to schemas') }}
      </button>
      <span class="text-sm text-muted-foreground">
        {{ currentStep }} / 2
      </span>
    </div>

    <!-- Step indicator -->
    <div class="flex gap-2">
      <div class="h-1 flex-1 rounded-full" :class="currentStep >= 1 ? 'bg-primary' : 'bg-muted'" />
      <div class="h-1 flex-1 rounded-full" :class="currentStep >= 2 ? 'bg-primary' : 'bg-muted'" />
    </div>

    <!-- Table name + schema selector -->
    <div class="grid gap-4 sm:grid-cols-2">
      <div class="space-y-2">
        <Label>{{ __('Table Name') }}</Label>
        <Input
          v-model="tableName"
          placeholder="products"
          class="font-mono"
        />
      </div>
      <div class="space-y-2">
        <Label>{{ __('Schema') }} <span class="text-destructive">*</span></Label>
        <Select v-model="selectedSchema">
          <SelectTrigger>
            <SelectValue :placeholder="__('Select schema')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="schema in schemas" :key="schema.name" :value="schema.name">
              {{ schema.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <!-- Step 1: Columns -->
    <div v-if="currentStep === 1">
      <h3 class="text-sm font-medium mb-3">{{ __('Columns') }}</h3>
      <StepColumns v-model="columns" />
    </div>

    <!-- Step 2: Validations -->
    <div v-if="currentStep === 2">
      <h3 class="text-sm font-medium mb-3">{{ __('Validations') }}</h3>
      <StepValidations
        :columns="columns"
        :model-value="validations"
        @update:model-value="Object.assign(validations, $event)"
      />
    </div>

    <!-- Actions -->
    <div class="flex justify-between pt-4 border-t">
      <Button
        v-if="currentStep === 2"
        variant="outline"
        @click="currentStep = 1"
      >
        {{ __('Back') }}
      </Button>
      <div v-else />

      <div class="flex gap-2">
        <Button variant="outline" @click="emit('cancel')">
          {{ __('Cancel') }}
        </Button>
        <Button
          v-if="currentStep === 1"
          :disabled="!canProceedToStep2"
          @click="currentStep = 2"
        >
          {{ __('Next') }}
        </Button>
        <Button
          v-if="currentStep === 2"
          :disabled="!canSubmit || submitting"
          @click="submit"
        >
          <Loader2 v-if="submitting" class="h-4 w-4 mr-2 animate-spin" />
          {{ __('Create Table') }}
        </Button>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Verify compiles**

Run: `npx vue-tsc --noEmit`
Expected: No type errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/schema/CreateTableWizard.vue
git commit -m "feat(schema-builder): add CreateTableWizard component with 2-step flow"
```

---

## Task 8: SchemaBrowser.vue Integration

**Files:**
- Modify: `resources/js/components/schema/SchemaBrowser.vue`

Depends on: Task 7 (CreateTableWizard.vue)

- [ ] **Step 1: Add imports**

Add after existing imports in `<script setup>`:

```typescript
import CreateTableWizard from '@/components/schema/CreateTableWizard.vue'
```

- [ ] **Step 2: Add 'create-table' view type and state**

Change the `view` ref:

```typescript
const view = ref<'folders' | 'browser' | 'create-table'>('folders')
const creatingForSchema = ref<string | null>(null)
```

- [ ] **Step 3: Add navigation helpers**

Add after `createSchema` function:

```typescript
const openCreateTable = (schemaName?: string) => {
  creatingForSchema.value = schemaName ?? null
  view.value = 'create-table'
}

const handleTableCreated = async () => {
  view.value = 'folders'
  creatingForSchema.value = null
  await loadSchemas()
}

const cancelCreateTable = () => {
  view.value = 'folders'
  creatingForSchema.value = null
}
```

- [ ] **Step 4: Add [New Table] buttons to schema folder cards**

Inside each schema card button element, add a "New Table" button. After the existing schema card content (before closing `</button>` of the card), add an overlay button:

In the folders view, inside each schema card `<button>`, after the table list section, add:

```html
<!-- Add inside schema card, after the table list div -->
<div class="mt-3 pt-3 border-t">
  <Button
    v-if="canEdit('databases')"
    variant="ghost"
    size="sm"
    class="w-full text-xs"
    @click.stop="openCreateTable(schema.name)"
  >
    <Plus class="h-3 w-3 mr-1" />
    {{ __('New Table') }}
  </Button>
</div>
```

- [ ] **Step 5: Add [New Table] button in browser view sidebar**

In the browser view sidebar, after the schema name header div (with `FolderOpen` icon), add:

```html
<Button
  v-if="canEdit('databases')"
  variant="ghost"
  size="sm"
  class="w-full mt-2"
  @click="openCreateTable(selectedSchema!)"
>
  <Plus class="h-3.5 w-3.5 mr-1.5" />
  {{ __('New Table') }}
</Button>
```

Add this inside the sidebar, after the schema name badge section and before the table list.

- [ ] **Step 6: Add 'create-table' view branch**

After the `<!-- BROWSER VIEW -->` closing `</div>` and before the `<!-- Create Schema Dialog -->`, add:

```html
<!-- CREATE TABLE VIEW -->
<CreateTableWizard
  v-if="view === 'create-table'"
  :database-id="databaseId"
  :schemas="schemas"
  :pre-selected-schema="creatingForSchema ?? undefined"
  @cancel="cancelCreateTable"
  @created="handleTableCreated"
/>
```

- [ ] **Step 7: Wrap folders + browser views with v-if**

Add `v-if="view !== 'create-table'"` condition to hide folders/browser when wizard is active. Wrap the folders view div and the browser view div in a template with this condition, or add `v-if` to each:

On the `<div v-if="view === 'folders'">` line, it already handles this since view would be `'create-table'`.

On the `<div v-else>` (browser view), change to `<div v-else-if="view === 'browser'">`.

- [ ] **Step 8: Verify everything renders**

Run: `npm run build` or check in browser
Expected: No errors, Schema tab shows schema cards, [+ New Table] button visible

- [ ] **Step 9: Commit**

```bash
git add resources/js/components/schema/SchemaBrowser.vue
git commit -m "feat(schema-builder): integrate CreateTableWizard into SchemaBrowser"
```

---

## Task 9: Translations

**Files:**
- Modify: `lang/pt.json`
- Modify: `lang/en.json`
- Modify: `lang/es.json`

- [ ] **Step 1: Add missing translation keys**

Add these keys to all 3 language files:

**en.json additions:**
```json
"Table Name": "Table Name",
"Add Column": "Add Column",
"Create Table": "Create Table",
"Failed to create table": "Failed to create table",
"Back to schemas": "Back to schemas",
"No columns defined. Go back to Step 1 to add columns.": "No columns defined. Go back to Step 1 to add columns.",
"rules": "rules",
"Select schema": "Select schema",
"Length": "Length",
"Nullable": "Nullable",
"Default": "Default",
"PK": "PK",
"Validations": "Validations",
"Next": "Next"
```

**pt.json additions:**
```json
"Table Name": "Nome da Tabela",
"Add Column": "Adicionar Coluna",
"Create Table": "Criar Tabela",
"Failed to create table": "Falha ao criar tabela",
"Back to schemas": "Voltar para schemas",
"No columns defined. Go back to Step 1 to add columns.": "Nenhuma coluna definida. Volte ao Passo 1 para adicionar colunas.",
"rules": "regras",
"Select schema": "Selecionar schema",
"Length": "Comprimento",
"Nullable": "Nulável",
"Default": "Padrão",
"PK": "PK",
"Validations": "Validações",
"Next": "Próximo"
```

**es.json additions:**
```json
"Table Name": "Nombre de Tabla",
"Add Column": "Agregar Columna",
"Create Table": "Crear Tabla",
"Failed to create table": "Error al crear tabla",
"Back to schemas": "Volver a schemas",
"No columns defined. Go back to Step 1 to add columns.": "Sin columnas definidas. Vuelve al Paso 1 para agregar columnas.",
"rules": "reglas",
"Select schema": "Seleccionar schema",
"Length": "Longitud",
"Nullable": "Nullable",
"Default": "Predeterminado",
"PK": "PK",
"Validations": "Validaciones",
"Next": "Siguiente"
```

Also add all ValidationPreset labels as translation keys:

```json
"Required": "Required" / "Obrigatório" / "Obligatorio",
"Min Length": "Min Length" / "Comprimento Mín" / "Longitud Mín",
"Max Length": "Max Length" / "Comprimento Máx" / "Longitud Máx",
"Min Value": "Min Value" / "Valor Mín" / "Valor Mín",
"Max Value": "Max Value" / "Valor Máx" / "Valor Máx",
"Must be integer": "Must be integer" / "Deve ser inteiro" / "Debe ser entero",
"Must be numeric": "Must be numeric" / "Deve ser numérico" / "Debe ser numérico",
"Regex": "Regex" / "Regex" / "Regex",
"Unique in table": "Unique in table" / "Único na tabela" / "Único en tabla",
"Exists in table": "Exists in table" / "Existe na tabela" / "Existe en tabla",
"Must be email": "Must be email" / "Deve ser email" / "Debe ser email",
"Must be URL": "Must be URL" / "Deve ser URL" / "Debe ser URL",
"Must be UUID": "Must be UUID" / "Deve ser UUID" / "Debe ser UUID",
"Must be date": "Must be date" / "Deve ser data" / "Debe ser fecha",
"Must be boolean": "Must be boolean" / "Deve ser booleano" / "Debe ser booleano",
"In list": "In list" / "Na lista" / "En lista",
"Only letters": "Only letters" / "Somente letras" / "Solo letras",
"Letters + numbers": "Letters + numbers" / "Letras + números" / "Letras + números",
"Letters + dash/underscore": "Letters + dash/underscore" / "Letras + traço/underline" / "Letras + guion/guion bajo"
```

- [ ] **Step 2: Run translation validation test**

Run: `php artisan test tests/Feature/Lang/TranslationKeysTest.php`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add lang/pt.json lang/en.json lang/es.json
git commit -m "feat(schema-builder): add table creation translation keys"
```

---

## Task 10: Feature Test — CreateTable

**Files:**
- Create: `tests/Feature/SchemaBuilder/CreateTableTest.php`

Depends on: Task 1 (ValidationPresetEnum), Task 2 (ValidationRuleMapper)

- [ ] **Step 1: Write feature test**

Test the full POST endpoint creates table in PostgreSQL and stores metadata.

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\SchemaBuilder;

use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Database $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        $this->database = Database::factory()->create([
            'status' => 'ready',
        ]);
    }

    public function test_can_create_table_with_columns(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'products',
                'schema' => 'public',
                'columns' => [
                    [
                        'name' => 'id',
                        'type' => 'uuid',
                        'nullable' => false,
                        'default_value' => 'gen_random_uuid()',
                        'is_primary_key' => true,
                    ],
                    [
                        'name' => 'name',
                        'type' => 'varchar',
                        'nullable' => false,
                        'length' => 255,
                    ],
                    [
                        'name' => 'price',
                        'type' => 'decimal',
                        'nullable' => false,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('toast');

        // Metadata was stored
        $this->assertDatabaseHas('database_table_metadata', [
            'database_id' => $this->database->id,
            'schema_name' => 'public',
            'table_name' => 'products',
        ]);

        // Migration was recorded
        $this->assertDatabaseHas('system_migrations', [
            'database_id' => $this->database->id,
            'table_name' => 'products',
            'operation' => 'add_column',
            'status' => 'executed',
        ]);
    }

    public function test_can_create_table_with_validations(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'orders',
                'schema' => 'public',
                'columns' => [
                    ['name' => 'id', 'type' => 'uuid', 'is_primary_key' => true],
                    ['name' => 'total', 'type' => 'decimal', 'nullable' => false],
                ],
                'validations' => [
                    'total' => [
                        'required' => true,
                        'min_value' => '0',
                    ],
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('database_table_metadata', [
            'database_id' => $this->database->id,
            'table_name' => 'orders',
        ]);

        $metadata = $this->database->tableMetadata()->where('table_name', 'orders')->first();
        $this->assertNotNull($metadata);
        $this->assertEquals([
            'total' => [
                'required' => true,
                'min_value' => '0',
            ],
        ], $metadata->validations);
    }

    public function test_validates_table_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'INVALID-NAME',
                'schema' => 'public',
                'columns' => [
                    ['name' => 'id', 'type' => 'uuid'],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_validates_columns_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'test',
                'schema' => 'public',
                'columns' => [],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('columns');
    }

    public function test_validates_column_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'test',
                'schema' => 'public',
                'columns' => [
                    ['name' => 'id', 'type' => 'invalid_type'],
                ],
            ]);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 2: Run test**

Run: `php artisan test tests/Feature/SchemaBuilder/CreateTableTest.php`
Expected: PASS (backend store() already implemented)

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/SchemaBuilder/CreateTableTest.php
git commit -m "test(schema-builder): add feature tests for table creation endpoint"
```

---

## Self-Review

**Spec coverage:**
- Validation presets table → Task 1 (enum) + Task 5 (UI) ✅
- ValidationRuleMapper → Task 2 ✅
- Column editor → Task 3 ✅
- Step columns → Task 4 ✅
- Step validations → Task 6 ✅
- Create table wizard → Task 7 ✅
- SchemaBrowser integration → Task 8 ✅
- Translations → Task 9 ✅
- Feature tests → Task 10 ✅
- Entry points (3 buttons) → Task 8 ✅

**Placeholder scan:** No TBD/TODO found.

**Type consistency:**
- `ColumnDefinition` used consistently across StepColumns, StepValidations, CreateTableWizard
- `ValidationPresetType` defined in schema.ts, used in ValidationPresets
- Backend `ValidationPresetEnum` values match frontend `ValidationPresetType` union
- API payload field names (`name`, `schema`, `columns`, `validations`) match `CreateTableRequest` validation rules
